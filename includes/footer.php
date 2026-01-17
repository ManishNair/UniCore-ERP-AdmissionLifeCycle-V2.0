<?php
/**
 * includes/footer.php - Global Layout Closer
 * This file closes the main content wrapper and the body tags opened in header.php
 */
?>
        <footer class="mt-auto py-6 px-12 border-t border-slate-100 bg-white/50">
            <div class="flex justify-between items-center">
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest italic">
                    &copy; <?= date('Y') ?> UniCore Cloud ERP <span class="mx-2 text-slate-200">|</span> V2.0 Stable Build
                </p>
                <div class="flex gap-4">
                    <span class="text-[8px] font-black text-blue-500 bg-blue-50 px-2 py-1 rounded uppercase">System Active</span>
                    <span class="text-[8px] font-black text-slate-400 uppercase tracking-tighter italic">
                        Server Time: <?= date('H:i:s T') ?>
                    </span>
                </div>
            </div>
        </footer>

    </div> </body>
</html>

<script>
    /**
     * Sidebar Module Search Logic
     * Filters navigation items in real-time
     */
    const moduleSearch = document.getElementById('moduleSearch');
    if (moduleSearch) {
        moduleSearch.addEventListener('input', function(e) {
            const term = e.target.value.toLowerCase();
            const links = document.querySelectorAll('.nav-link');
            const groups = document.querySelectorAll('.nav-group');

            links.forEach(link => {
                const span = link.querySelector('span');
                if (span) {
                    const text = span.textContent.toLowerCase();
                    const parentLi = link.closest('li');
                    if (text.includes(term)) {
                        parentLi.style.display = 'block';
                    } else {
                        parentLi.style.display = 'none';
                    }
                }
            });

            // Hide section headers if no visible links exist in that group
            groups.forEach(group => {
                const visibleLinks = group.querySelectorAll('li[style="display: block;"]').length;
                if (visibleLinks === 0 && term !== "") {
                    group.classList.add('hidden');
                } else {
                    group.classList.remove('hidden');
                }
            });
        });
    }

    /**
     * Auto-Scroll Sidebar to Active Page
     */
    window.addEventListener('DOMContentLoaded', () => {
        const activeLink = document.querySelector('.nav-link.bg-blue-600');
        if (activeLink) {
            activeLink.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });
</script>