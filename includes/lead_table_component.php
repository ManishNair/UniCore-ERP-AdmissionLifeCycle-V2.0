<?php
// includes/lead_table_component.php
// Logic: Handles UI display for leads with role-based action authority

$current_user_id = $_SESSION['user_id'] ?? 0;
$is_chancellor = (isset($_SESSION['role']) && $_SESSION['role'] === 'Chancellor');
?>

<div class="bg-white rounded-[35px] border border-slate-200 overflow-hidden shadow-sm">
    <table class="w-full text-left border-collapse">
        <thead class="bg-slate-50 border-b border-slate-100">
            <tr>
                <th class="px-8 py-5 text-[10px] font-black uppercase text-slate-400 tracking-widest">Student & Institution</th>
                <th class="px-8 py-5 text-[10px] font-black uppercase text-slate-400 tracking-widest">Ownership</th>
                <th class="px-8 py-5 text-[10px] font-black uppercase text-slate-400 tracking-widest text-center">Probability</th>
                <th class="px-8 py-5 text-right text-[10px] font-black uppercase text-slate-400 tracking-widest">Action</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-50">
            <?php if (empty($leads)): ?>
                <tr>
                    <td colspan="4" class="px-8 py-20 text-center">
                        <div class="flex flex-col items-center">
                            <i class="fas fa-layer-group text-slate-200 text-4xl mb-4"></i>
                            <p class="text-slate-400 font-bold text-xs uppercase tracking-widest">No active leads found in this scope</p>
                        </div>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($leads as $lead): ?>
                    <?php 
                        // Core Logic: Authority Check
                        $is_mine = ($lead['counselor_id'] == $current_user_id);
                        $is_unassigned = empty($lead['counselor_id']);
                        $can_handle = ($is_mine || $is_chancellor);
                    ?>
                    <tr class="hover:bg-slate-50/80 transition-all group <?= (!$can_handle) ? 'opacity-60 bg-slate-50/30' : '' ?>">
                        
                        <td class="px-8 py-5">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center font-black text-xs transition-all <?= $can_handle ? 'bg-blue-600 text-white shadow-lg shadow-blue-500/20' : 'bg-slate-200 text-slate-500' ?>">
                                    <?= strtoupper(substr($lead['full_name'], 0, 1)) ?>
                                </div>
                                <div>
                                    <div class="font-bold text-slate-900 text-sm italic"><?= htmlspecialchars($lead['full_name']) ?></div>
                                    <div class="text-[9px] font-black text-blue-500 uppercase tracking-tighter"><?= htmlspecialchars($lead['college_name'] ?? 'General') ?></div>
                                </div>
                            </div>
                        </td>

                        <td class="px-8 py-5">
                            <?php if ($is_mine): ?>
                                <span class="inline-flex items-center px-3 py-1 bg-emerald-50 text-emerald-600 text-[9px] font-black rounded-lg border border-emerald-100 uppercase tracking-widest">
                                    <i class="fas fa-user-check mr-1.5"></i> Assigned to You
                                </span>
                            <?php elseif ($is_unassigned): ?>
                                <span class="inline-flex items-center px-3 py-1 bg-slate-100 text-slate-500 text-[9px] font-black rounded-lg border border-slate-200 uppercase tracking-widest">
                                    <i class="fas fa-thumbtack mr-1.5"></i> Unassigned
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center px-3 py-1 bg-slate-50 text-slate-400 text-[9px] font-black rounded-lg border border-slate-100 uppercase tracking-widest">
                                    <i class="fas fa-user-friends mr-1.5"></i> <?= htmlspecialchars($lead['counselor_name'] ?? 'Staff') ?>
                                </span>
                            <?php endif; ?>
                        </td>

                        <td class="px-8 py-5">
                            <div class="flex flex-col items-center gap-1">
                                <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden max-w-[80px]">
                                    <div class="h-full <?= $lead['conversion_probability'] > 70 ? 'bg-emerald-500' : ($lead['conversion_probability'] > 40 ? 'bg-amber-500' : 'bg-rose-500') ?>" 
                                         style="width: <?= $lead['conversion_probability'] ?>%"></div>
                                </div>
                                <span class="text-[9px] font-black text-slate-400"><?= $lead['conversion_probability'] ?>%</span>
                            </div>
                        </td>

                        <td class="px-8 py-5 text-right">
                            <?php if ($can_handle): ?>
                                <a href="compliance_queue.php?id=<?= $lead['id'] ?>" 
                                   class="inline-flex items-center bg-slate-900 text-white px-5 py-2.5 rounded-xl text-[9px] font-black uppercase tracking-widest hover:bg-blue-600 transition-all shadow-xl shadow-slate-200">
                                    <i class="fas fa-shield-check mr-2 text-[10px]"></i> Handle Lead
                                </a>
                            <?php else: ?>
                                <a href="lead_profile.php?id=<?= $lead['id'] ?>" 
                                   class="inline-flex items-center bg-white border border-slate-200 text-slate-400 px-5 py-2.5 rounded-xl text-[9px] font-black uppercase tracking-widest hover:text-slate-900 hover:border-slate-400 transition-all">
                                    <i class="fas fa-eye mr-2 text-[10px]"></i> View Profile
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>