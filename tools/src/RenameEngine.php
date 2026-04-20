<?php
namespace Easeo\Tools;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;

class RenameEngine {
    public function __construct(
        private string $legacyDir,
        private string $targetBase,
        private array $callerDirs,
    ) {}

    /**
     * @return array diff-samenvatting (dry-run) of [] na succesvolle rename
     */
    public function rename(array $mapping, bool $dryRun = false): array {
        $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        $printer = new Standard();

        $engineFile = $this->legacyDir . '/' . $mapping['engine'] . '.php';
        if (!is_file($engineFile)) {
            throw new \RuntimeException("Engine-bestand niet gevonden: $engineFile");
        }

        $ast = $parser->parse(file_get_contents($engineFile));
        $newClass = $this->buildClassFromFunctions($ast, $mapping);

        $targetDir = $this->targetBase . '/' . $mapping['subdir'];
        $targetFile = $targetDir . '/' . $mapping['class'] . '.php';

        $classCode = "<?php\nnamespace {$mapping['namespace']};\n\n" . $printer->prettyPrint([$newClass]) . "\n";

        $callerChanges = $this->rewriteCallers($mapping, $parser, $printer);

        if ($dryRun) {
            return [
                'target_file' => $targetFile,
                'target_code_preview' => substr($classCode, 0, 500),
                'caller_changes' => $callerChanges,
            ];
        }

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }
        file_put_contents($targetFile, $classCode);
        unlink($engineFile);

        foreach ($callerChanges as $file => $newContent) {
            file_put_contents($file, $newContent);
        }

        return [];
    }

    private function buildClassFromFunctions(array $ast, array $mapping): Stmt\Class_ {
        $methods = [];
        foreach ($ast as $stmt) {
            if (!$stmt instanceof Stmt\Function_) continue;
            $fnName = (string)$stmt->name;
            if (!isset($mapping['functions'][$fnName])) continue;

            $methodName = $mapping['functions'][$fnName];
            $method = new Stmt\ClassMethod($methodName, [
                'flags' => Stmt\Class_::MODIFIER_PUBLIC | Stmt\Class_::MODIFIER_STATIC,
                'params' => $stmt->params,
                'returnType' => $stmt->returnType,
                'stmts' => $stmt->stmts,
            ]);
            $methods[] = $method;
        }

        return new Stmt\Class_($mapping['class'], ['stmts' => $methods]);
    }

    private function rewriteCallers(array $mapping, $parser, Standard $printer): array {
        $changes = [];
        $fullClass = $mapping['namespace'] . '\\' . $mapping['class'];

        foreach ($this->callerDirs as $dir) {
            $files = $this->findPhpFiles($dir);
            foreach ($files as $file) {
                $source = file_get_contents($file);
                $ast = $parser->parse($source);
                if ($ast === null) continue;

                $visitor = new CallSiteRewriter($mapping['functions'], $mapping['class']);
                $traverser = new NodeTraverser();
                $traverser->addVisitor($visitor);
                $newAst = $traverser->traverse($ast);

                if ($visitor->hasChanges()) {
                    $newAst = $this->ensureUseStatement($newAst, $fullClass);
                    $changes[$file] = "<?php\n" . $printer->prettyPrint(array_slice($newAst, 0)) . "\n";
                }
            }
        }

        return $changes;
    }

    private function ensureUseStatement(array $ast, string $fullClass): array {
        // Find Namespace_ node — if present, insert use INSIDE it
        foreach ($ast as $topStmt) {
            if ($topStmt instanceof Stmt\Namespace_) {
                // Check if use already exists in namespace
                foreach ($topStmt->stmts as $nsStmt) {
                    if ($nsStmt instanceof Stmt\Use_) {
                        foreach ($nsStmt->uses as $use) {
                            if ((string)$use->name === $fullClass) return $ast;
                        }
                    }
                }
                $useStmt = new Stmt\Use_([new Stmt\UseUse(new Name($fullClass))]);
                // Insert after existing Declare_/Use_ in namespace, before first non-use
                $insertIdx = 0;
                foreach ($topStmt->stmts as $i => $s) {
                    if ($s instanceof Stmt\Declare_ || $s instanceof Stmt\Use_) {
                        $insertIdx = $i + 1;
                    } else {
                        break;
                    }
                }
                array_splice($topStmt->stmts, $insertIdx, 0, [$useStmt]);
                return $ast;
            }
        }

        // No namespace — check top-level uses + declares
        foreach ($ast as $stmt) {
            if ($stmt instanceof Stmt\Use_) {
                foreach ($stmt->uses as $use) {
                    if ((string)$use->name === $fullClass) return $ast;
                }
            }
        }

        $useStmt = new Stmt\Use_([new Stmt\UseUse(new Name($fullClass))]);
        // Insert after top-level Declare_ / Use_, before first other statement
        $insertIdx = 0;
        foreach ($ast as $i => $s) {
            if ($s instanceof Stmt\Declare_ || $s instanceof Stmt\Use_) {
                $insertIdx = $i + 1;
            } else {
                break;
            }
        }
        array_splice($ast, $insertIdx, 0, [$useStmt]);
        return $ast;
    }

    private function findPhpFiles(string $dir): array {
        $files = [];
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));
        foreach ($it as $f) {
            if ($f->isFile() && $f->getExtension() === 'php') {
                $files[] = $f->getPathname();
            }
        }
        return $files;
    }
}

class CallSiteRewriter extends NodeVisitorAbstract {
    private bool $changed = false;

    public function __construct(private array $fnMap, private string $className) {}

    public function leaveNode(Node $node) {
        if ($node instanceof FuncCall
            && $node->name instanceof Name
            && !$node->name instanceof Name\FullyQualified
        ) {
            $fnName = (string)$node->name;
            if (isset($this->fnMap[$fnName])) {
                $this->changed = true;
                return new Node\Expr\StaticCall(
                    new Name($this->className),
                    new Identifier($this->fnMap[$fnName]),
                    $node->args
                );
            }
        }
        return null;
    }

    public function hasChanges(): bool { return $this->changed; }
}
