
</div><!-- /admin-content -->

<!-- Media picker modal -->
<div class="media-modal" id="media-picker-modal">
    <div class="media-modal-content">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold">Media kiezen</h3>
            <button onclick="closeMediaPicker()" class="text-gray-400 hover:text-white">&times;</button>
        </div>
        <div id="media-picker-grid" class="grid grid-cols-4 gap-3">
            <!-- Loaded via JS -->
        </div>
    </div>
</div>

<script>
// Mobile sidebar toggle
document.getElementById('admin-mobile-toggle')?.addEventListener('click', function() {
    document.getElementById('admin-sidebar').classList.toggle('open');
});

// Media picker
var mediaPickerTarget = null;

function openMediaPicker(targetId) {
    mediaPickerTarget = targetId;
    var modal = document.getElementById('media-picker-modal');
    modal.classList.add('active');

    fetch('/beheer/?tab=media&action=list&format=json')
        .then(r => r.json())
        .then(data => {
            var grid = document.getElementById('media-picker-grid');
            grid.innerHTML = '';
            (data.files || []).forEach(function(file) {
                var div = document.createElement('div');
                div.className = 'cursor-pointer rounded overflow-hidden border border-gray-700 hover:border-blue-500 transition-colors';
                div.innerHTML = '<img src="' + file.thumb + '" class="w-full h-24 object-cover" alt="">';
                div.onclick = function() {
                    document.getElementById(mediaPickerTarget).value = file.url;
                    closeMediaPicker();
                };
                grid.appendChild(div);
            });
        })
        .catch(function() {
            document.getElementById('media-picker-grid').innerHTML = '<p class="col-span-4 text-gray-500 text-sm">Geen media gevonden.</p>';
        });
}

function closeMediaPicker() {
    document.getElementById('media-picker-modal').classList.remove('active');
    mediaPickerTarget = null;
}

// Close modal on backdrop click
document.getElementById('media-picker-modal')?.addEventListener('click', function(e) {
    if (e.target === this) closeMediaPicker();
});
</script>
<script>
// Help tooltips
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.help-tooltip').forEach(function(el) {
        var text = document.createElement('span');
        text.className = 'help-text';
        text.textContent = el.getAttribute('data-help');
        el.appendChild(text);

        el.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            document.querySelectorAll('.help-tooltip.active').forEach(function(other) {
                if (other !== el) other.classList.remove('active');
            });
            el.classList.toggle('active');
        });
    });

    document.addEventListener('click', function() {
        document.querySelectorAll('.help-tooltip.active').forEach(function(el) {
            el.classList.remove('active');
        });
    });
});
</script>
<div style="text-align:center;padding:1.5rem 0 0.5rem;">
    <a href="https://easeo.nl" target="_blank" rel="noopener" style="color:#64748b;font-size:0.7rem;">Powered by EASEO &mdash; Digital Agency met kracht</a>
</div>
</body>
</html>
