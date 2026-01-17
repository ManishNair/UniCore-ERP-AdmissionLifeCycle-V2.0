<?php
// includes/sidebar.php - V2.0 Dynamic RBAC & Pure Navigation
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'config/db.php';

$current_page = basename($_SERVER['PHP_SELF']);

/**
 * V2.0 DYNAMIC PERMISSIONS
 * We no longer use $is_admin = ($user_role === 'Superadmin');
 * Instead, we use the has_perm() function defined in header.php.
 */
?>
<aside id="mainSidebar" class="w-72 bg-[#0f172a] h-screen fixed left-0 top-0 flex flex-col border-r border-slate-800 z-50 transition-all duration-300">
    
    <div class="p-8 border-b border-slate-800 bg-[#0f172a] shrink-0">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 bg-blue-600 rounded-xl flex items-center justify-center shadow-lg shadow-blue-500/20">
                <i class="fas fa-graduation-cap text-white text-xl"></i>
            </div>
            <div>
                <h2 class="text-white font-black uppercase italic tracking-tighter text-sm leading-none">UniCore <span class="text-blue-500">Cloud</span></h2>
                <p class="text-[9px] font-bold text-slate-500 uppercase tracking-widest mt-1">V2.0 RBAC Protected</p>
            </div>
        </div>

        <div class="relative group">
            <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-600 text-[10px] group-focus-within:text-blue-500 transition-colors"></i>
            <input type="text" id="moduleSearch" placeholder="SEARCH MODULES..." 
                   class="w-full bg-slate-900 border border-slate-800 rounded-xl py-3 pl-10 pr-4 text-[9px] font-black text-slate-300 placeholder:text-slate-600 outline-none focus:border-blue-500/50 transition-all uppercase tracking-widest">
        </div>
    </div>

    <nav id="sidebarNav" class="flex-1 overflow-y-auto py-6 custom-scrollbar scroll-smooth px-4">
        
        <div class="nav-group mb-8">
            <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-3 px-4">University Hub</p>
            <ul class="space-y-1">
                <li><a href="index.php" class="nav-link flex items-center gap-4 px-4 py-3 rounded-2xl text-slate-400 hover:bg-slate-800/50 hover:text-white transition-all group <?= $current_page == 'index.php' ? 'bg-blue-600 text-white shadow-md' : '' ?>">
                    <i class="fas fa-th-large text-xs"></i>
                    <span class="text-[10px] font-black uppercase tracking-widest">Global Dashboard</span>
                </a></li>

                <?php if (has_perm('bulk_assign')): ?> 
                <li><a href="bulk_assign.php" class="nav-link flex items-center gap-4 px-4 py-3 rounded-2xl text-slate-400 hover:bg-slate-800/50 hover:text-white transition-all group <?= $current_page == 'bulk_assign.php' ? 'bg-blue-600 text-white' : '' ?>">
                    <i class="fas fa-users-cog text-xs"></i>
                    <span class="text-[10px] font-black uppercase tracking-widest">Lead Assignment Hub</span>
                </a></li>
                <?php endif; ?>

                <?php if (has_perm('manage_staff')): ?>
                <li><a href="manage_users.php" class="nav-link flex items-center gap-4 px-4 py-3 rounded-2xl text-slate-400 hover:bg-slate-800/50 hover:text-white transition-all group <?= $current_page == 'manage_users.php' ? 'bg-blue-600 text-white' : '' ?>">
                    <i class="fas fa-user-shield text-xs"></i>
                    <span class="text-[10px] font-black uppercase tracking-widest">Staff Management</span>
                </a></li>
                <?php endif; ?>
            </ul>
        </div>

        <div class="nav-group mb-8">
            <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-3 px-4">Admission Pipeline</p>
            <ul class="space-y-1">
                <li><a href="add_lead.php" class="nav-link flex items-center gap-4 px-4 py-3 rounded-2xl text-slate-400 hover:bg-slate-800/50 hover:text-white transition-all group <?= $current_page == 'add_lead.php' ? 'bg-blue-600 text-white' : '' ?>">
                    <i class="fas fa-user-plus text-xs text-emerald-400"></i>
                    <span class="text-[10px] font-black uppercase tracking-widest">Add Manual Lead</span>
                </a></li>
                <?php if (has_perm('social_router')): ?>
                <li><a href="social_router.php" class="nav-link flex items-center justify-between px-4 py-3 rounded-2xl text-slate-400 hover:bg-slate-800/50 hover:text-white transition-all group <?= $current_page == 'social_router.php' ? 'bg-blue-600 text-white' : '' ?>">
                    <div class="flex items-center gap-4">
                        <i class="fas fa-project-diagram text-xs"></i>
                        <span class="text-[10px] font-black uppercase tracking-widest">Social Lead Router</span>
                    </div>
                    <span class="bg-rose-500 text-[8px] px-1.5 py-0.5 rounded text-white">New</span>
                </a></li>
                <?php endif; ?>
                <?php if (has_perm('view_compliance')): ?>
                <li><a href="compliance_queue.php" class="nav-link flex items-center gap-4 px-4 py-3 rounded-2xl text-slate-400 hover:bg-slate-800/50 hover:text-white transition-all group <?= ($current_page == 'compliance_queue.php' || $current_page == 'compliance_desk.php') ? 'bg-blue-600 text-white' : '' ?>">
                    <i class="fas fa-user-check text-xs text-amber-400"></i>
                    <span class="text-[10px] font-black uppercase tracking-widest">Compliance Desk</span>
                </a></li>
                <?php endif; ?>

                <?php if (has_perm('view_finance_gate')): ?>
                <li><a href="financial_gate.php" class="nav-link flex items-center gap-4 px-4 py-3 rounded-2xl text-slate-400 hover:bg-slate-800/50 hover:text-white transition-all group <?= $current_page == 'financial_gate.php' ? 'bg-blue-600 text-white' : '' ?>">
                    <i class="fas fa-file-invoice-dollar text-xs text-blue-400"></i>
                    <span class="text-[10px] font-black uppercase tracking-widest">Financial Gate</span>
                </a></li>
                <?php endif; ?>
            </ul>
        </div>

        <?php if (has_perm('view_audit') || has_perm('manage_staff')): ?> 
        <div class="nav-group mb-8">
            <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-3 px-4">Governance & Audit</p>
            <ul class="space-y-1">
                <?php if (has_perm('view_audit')): ?>
				<li><a href="audit_trail.php" class="nav-link flex items-center gap-4 px-4 py-3 rounded-2xl text-slate-400 hover:bg-slate-800/50 hover:text-white transition-all group <?= $current_page == 'audit_trail.php' ? 'bg-blue-600 text-white' : '' ?>">
                    <i class="fas fa-fingerprint text-xs text-emerald-400"></i>
                    <span class="text-[10px] font-black uppercase tracking-widest">Lifecycle Audit Trail</span>
                </a></li>
				 <?php endif; ?>
				<li><a href="role_permissions.php" class="nav-link flex items-center gap-4 px-4 py-3 rounded-2xl text-slate-400 hover:bg-slate-800/50 hover:text-white transition-all group <?= $current_page == 'role_permissions.php' ? 'bg-blue-600 text-white' : '' ?>">
                    <i class="fa-solid fa-user-gear"></i>
                    <span class="text-[10px] font-black uppercase tracking-widest">Access Matrix</span>
                </a></li>
            </ul>
        </div>
		
        <?php endif; ?>

        <?php if (has_perm('manage_staff')): ?> 
        <div class="nav-group mb-8 pt-4 border-t border-slate-800/50">
            <p class="text-[9px] font-black text-rose-500 uppercase tracking-widest mb-3 px-4 italic">Developer Mode</p>
            <ul class="space-y-1">
                <li><a href="api/webhook_simulator.php" target="_blank" class="nav-link flex items-center gap-4 px-4 py-3 rounded-2xl text-slate-500 hover:bg-rose-500/10 transition-all">
                    <i class="fas fa-vial text-xs"></i>
                    <span class="text-[10px] font-black uppercase tracking-widest">Simulate Ingest (FB/IG)</span>
                </a></li>
                <li><a href="api/test_wa.php" target="_blank" class="nav-link flex items-center gap-4 px-4 py-3 rounded-2xl text-slate-500 hover:bg-emerald-500/10 transition-all">
                    <i class="fab fa-whatsapp text-xs text-emerald-500"></i>
                    <span class="text-[10px] font-black uppercase tracking-widest">WhatsApp Sim Test</span>
                </a></li>
                <li><a href="api/log_viewer.php" class="nav-link flex items-center gap-4 px-4 py-3 rounded-2xl text-slate-400 hover:bg-blue-500/10 transition-all <?= $current_page == 'log_viewer.php' ? 'text-blue-400' : '' ?>">
                    <i class="fas fa-terminal text-xs text-blue-400"></i>
                    <span class="text-[10px] font-black uppercase tracking-widest">Live Ingest Viewer</span>
                </a></li>
                <li><a href="api/test_compliance.php" class="nav-link flex items-center gap-4 px-4 py-3 rounded-2xl text-slate-500 hover:bg-rose-500/10 transition-all">
                    <i class="fas fa-microchip text-xs"></i>
                    <span class="text-[10px] font-black uppercase tracking-widest">AI Verification Test</span>
                </a></li>
            </ul>
        </div>
        <?php endif; ?>

    </nav>
</aside>

<style>
    .custom-scrollbar::-webkit-scrollbar { width: 3px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #1e293b; border-radius: 10px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #3b82f6; }
    .nav-group.hidden { display: none; }
</style>

<script>
    // Module Search Filter Logic
    document.getElementById('moduleSearch').addEventListener('input', function(e) {
        const term = e.target.value.toLowerCase();
        const links = document.querySelectorAll('.nav-link');
        const groups = document.querySelectorAll('.nav-group');

        links.forEach(link => {
            const text = link.querySelector('span').textContent.toLowerCase();
            const parentLi = link.closest('li');
            parentLi.style.display = text.includes(term) ? 'block' : 'none';
        });

        groups.forEach(group => {
            const visibleLinks = group.querySelectorAll('li[style="display: block;"]').length;
            group.classList.toggle('hidden', visibleLinks === 0 && term !== "");
        });
    });
</script>