const searchInput = document.querySelector('.nav-search input');
const searchBtn   = document.querySelector('.nav-search button');

if (searchInput) {
    let timer;
    let dropdown = null;

    function createDropdown() {
        if (dropdown) return;
        dropdown = document.createElement('div');
        dropdown.id = 'search-dropdown';
        dropdown.style.cssText = `
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
            z-index: 999;
            max-height: 400px;
            overflow-y: auto;
            margin-top: 4px;
        `;
        searchInput.parentElement.style.position = 'relative';
        searchInput.parentElement.appendChild(dropdown);
    }

    function removeDropdown() {
        if (dropdown) {
            dropdown.remove();
            dropdown = null;
        }
    }

    function renderResults(notes) {
        createDropdown();
        if (notes.length === 0) {
            dropdown.innerHTML = `
                <div style="padding:1rem;text-align:center;color:#888;font-size:0.9rem">
                    No notes found
                </div>`;
            return;
        }

        dropdown.innerHTML = notes.map(n => `
            <a href="/notes-platform/notes/view.php?id=${n.id}"
               style="display:flex;gap:12px;padding:0.75rem 1rem;text-decoration:none;
                      border-bottom:1px solid #f5f5f5;align-items:center"
               onmouseover="this.style.background='#f8f8ff'"
               onmouseout="this.style.background='#fff'">
                <div style="width:36px;height:36px;background:#EEEDFE;border-radius:8px;
                            display:flex;align-items:center;justify-content:center;
                            font-size:0.65rem;font-weight:700;color:#534AB7;flex-shrink:0">
                    ${getExt(n.file_path)}
                </div>
                <div style="flex:1;min-width:0">
                    <div style="font-size:0.9rem;font-weight:500;color:#1a1a2e;
                                white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                        ${n.title}
                    </div>
                    <div style="font-size:0.78rem;color:#888;margin-top:2px">
                        ${n.subject_name} · ⬇ ${n.download_count} · by ${n.uploader}
                    </div>
                </div>
            </a>
        `).join('');

        // View all link
        const q = searchInput.value.trim();
        dropdown.innerHTML += `
            <a href="/notes-platform/index.php?q=${encodeURIComponent(q)}"
               style="display:block;padding:0.75rem 1rem;text-align:center;
                      font-size:0.85rem;color:#534AB7;font-weight:500;text-decoration:none">
                View all results for "${q}" →
            </a>`;
    }

    function getExt(filePath) {
        return filePath.split('.').pop().toUpperCase();
    }

    searchInput.addEventListener('keyup', function() {
        const q = this.value.trim();
        clearTimeout(timer);

        if (q.length < 2) {
            removeDropdown();
            return;
        }

        timer = setTimeout(() => {
            fetch(`/notes-platform/api/search.php?q=${encodeURIComponent(q)}`)
                .then(r => r.json())
                .then(data => renderResults(data))
                .catch(() => removeDropdown());
        }, 300);
    });

    // Hide on click outside
    document.addEventListener('click', function(e) {
        if (!searchInput.parentElement.contains(e.target)) {
            removeDropdown();
        }
    });

    // Don't submit form if dropdown is showing
    searchInput.closest('form').addEventListener('submit', function(e) {
        removeDropdown();
    });
}