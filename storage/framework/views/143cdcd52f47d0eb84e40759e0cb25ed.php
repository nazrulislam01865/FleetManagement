<?php $__env->startSection('title', 'Dues & Payroll | FleetMan'); ?>
<?php $__env->startSection('mobile-title', 'Dues'); ?>

<?php $__env->startSection('content'); ?>
<div class="page-section">
    <div id="dueListPage">
        <?php if (isset($component)) { $__componentOriginal9c1bf3ca5b4372ced6ff0d503060f43b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9c1bf3ca5b4372ced6ff0d503060f43b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.topbar','data' => ['items' => [['label' => 'Dues List']]]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.topbar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['items' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute([['label' => 'Dues List']])]); ?>
             <?php $__env->slot('actions', null, []); ?> 
                <button type="button" class="btn secondary" id="generatePayrollBtn" title="Payroll can be generated only from the 26th through the 30th of each month.">🗓 Generate Monthly Payroll</button>
             <?php $__env->endSlot(); ?>
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9c1bf3ca5b4372ced6ff0d503060f43b)): ?>
<?php $attributes = $__attributesOriginal9c1bf3ca5b4372ced6ff0d503060f43b; ?>
<?php unset($__attributesOriginal9c1bf3ca5b4372ced6ff0d503060f43b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9c1bf3ca5b4372ced6ff0d503060f43b)): ?>
<?php $component = $__componentOriginal9c1bf3ca5b4372ced6ff0d503060f43b; ?>
<?php unset($__componentOriginal9c1bf3ca5b4372ced6ff0d503060f43b); ?>
<?php endif; ?>

        <?php if (isset($component)) { $__componentOriginal33f656cc9906d43d1f23d50a43b5f3b3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal33f656cc9906d43d1f23d50a43b5f3b3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.title-card','data' => ['title' => 'Accounts Payable & Dues','subtitle' => 'Review and process driver salaries, employee salaries, vehicle rents, fuel recharges, and unpaid trip balances.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.title-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Accounts Payable & Dues','subtitle' => 'Review and process driver salaries, employee salaries, vehicle rents, fuel recharges, and unpaid trip balances.']); ?>
             <?php $__env->slot('action', null, []); ?> <button type="button" class="btn secondary" id="exportDuesBtn">Export CSV</button> <?php $__env->endSlot(); ?>
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal33f656cc9906d43d1f23d50a43b5f3b3)): ?>
<?php $attributes = $__attributesOriginal33f656cc9906d43d1f23d50a43b5f3b3; ?>
<?php unset($__attributesOriginal33f656cc9906d43d1f23d50a43b5f3b3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal33f656cc9906d43d1f23d50a43b5f3b3)): ?>
<?php $component = $__componentOriginal33f656cc9906d43d1f23d50a43b5f3b3; ?>
<?php unset($__componentOriginal33f656cc9906d43d1f23d50a43b5f3b3); ?>
<?php endif; ?>

        <div class="kpi">
            <?php if (isset($component)) { $__componentOriginalf0c0d749c1d866d6dda6395f8c5d46f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf0c0d749c1d866d6dda6395f8c5d46f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.kpi-card','data' => ['id' => 'dueKpiTotal','label' => 'Total Dues','value' => '0']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.kpi-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'dueKpiTotal','label' => 'Total Dues','value' => '0']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf0c0d749c1d866d6dda6395f8c5d46f6)): ?>
<?php $attributes = $__attributesOriginalf0c0d749c1d866d6dda6395f8c5d46f6; ?>
<?php unset($__attributesOriginalf0c0d749c1d866d6dda6395f8c5d46f6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf0c0d749c1d866d6dda6395f8c5d46f6)): ?>
<?php $component = $__componentOriginalf0c0d749c1d866d6dda6395f8c5d46f6; ?>
<?php unset($__componentOriginalf0c0d749c1d866d6dda6395f8c5d46f6); ?>
<?php endif; ?>
            <?php if (isset($component)) { $__componentOriginalf0c0d749c1d866d6dda6395f8c5d46f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf0c0d749c1d866d6dda6395f8c5d46f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.kpi-card','data' => ['id' => 'dueKpiPending','label' => 'Pending Dues','value' => '0']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.kpi-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'dueKpiPending','label' => 'Pending Dues','value' => '0']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf0c0d749c1d866d6dda6395f8c5d46f6)): ?>
<?php $attributes = $__attributesOriginalf0c0d749c1d866d6dda6395f8c5d46f6; ?>
<?php unset($__attributesOriginalf0c0d749c1d866d6dda6395f8c5d46f6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf0c0d749c1d866d6dda6395f8c5d46f6)): ?>
<?php $component = $__componentOriginalf0c0d749c1d866d6dda6395f8c5d46f6; ?>
<?php unset($__componentOriginalf0c0d749c1d866d6dda6395f8c5d46f6); ?>
<?php endif; ?>
            <?php if (isset($component)) { $__componentOriginalf0c0d749c1d866d6dda6395f8c5d46f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf0c0d749c1d866d6dda6395f8c5d46f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.kpi-card','data' => ['id' => 'dueKpiPaid','label' => 'Paid Amount','value' => '0']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.kpi-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'dueKpiPaid','label' => 'Paid Amount','value' => '0']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf0c0d749c1d866d6dda6395f8c5d46f6)): ?>
<?php $attributes = $__attributesOriginalf0c0d749c1d866d6dda6395f8c5d46f6; ?>
<?php unset($__attributesOriginalf0c0d749c1d866d6dda6395f8c5d46f6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf0c0d749c1d866d6dda6395f8c5d46f6)): ?>
<?php $component = $__componentOriginalf0c0d749c1d866d6dda6395f8c5d46f6; ?>
<?php unset($__componentOriginalf0c0d749c1d866d6dda6395f8c5d46f6); ?>
<?php endif; ?>
            <?php if (isset($component)) { $__componentOriginalf0c0d749c1d866d6dda6395f8c5d46f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf0c0d749c1d866d6dda6395f8c5d46f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.kpi-card','data' => ['id' => 'dueKpiSalary','label' => 'Salary Payload','value' => '0']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.kpi-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'dueKpiSalary','label' => 'Salary Payload','value' => '0']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf0c0d749c1d866d6dda6395f8c5d46f6)): ?>
<?php $attributes = $__attributesOriginalf0c0d749c1d866d6dda6395f8c5d46f6; ?>
<?php unset($__attributesOriginalf0c0d749c1d866d6dda6395f8c5d46f6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf0c0d749c1d866d6dda6395f8c5d46f6)): ?>
<?php $component = $__componentOriginalf0c0d749c1d866d6dda6395f8c5d46f6; ?>
<?php unset($__componentOriginalf0c0d749c1d866d6dda6395f8c5d46f6); ?>
<?php endif; ?>
        </div>

        <div class="card">
            <div class="filters">
                <input id="dueSearch" placeholder="Search by party ID, code, or type">
                <select id="dueFilterType" class="form-control" style="width:200px">
                    <option value="">All Types</option>
                    <option value="Fuel Recharge">Fuel Recharge</option>
                    <option value="Driver Salary">Driver Salary</option>
                    <option value="Employee Salary">Employee Salary</option>
                    <option value="Vehicle Rent">Vehicle Rent</option>
                    <option value="Trip Payment Balance">Trip Payment Balance</option>
                </select>
                <select id="dueFilterStatus" class="form-control" style="width:200px">
                    <option value="">All Statuses</option>
                    <option value="Pending">Pending</option>
                    <option value="Paid">Paid</option>
                    <option value="Cancelled">Cancelled</option>
                </select>
                <button type="button" class="btn light" id="clearDueFiltersBtn">Clear</button>
            </div>
            
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Created At</th>
                            <th>Code</th>
                            <th>Type</th>
                            <th>Party ID</th>
                            <th>Due Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="dueTbody">
                        <!-- Populated by JS -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Scripts for Dues specific page -->
<script>
    document.addEventListener('DOMContentLoaded', () => {
        let duesData = [];

        function renderDues() {
            const tbody = document.getElementById('dueTbody');
            tbody.innerHTML = '';

            const searchTerm = document.getElementById('dueSearch').value.toLowerCase();
            const filterType = document.getElementById('dueFilterType').value;
            const filterStatus = document.getElementById('dueFilterStatus').value;

            let total = 0, pending = 0, paidAmt = 0, salaryAmt = 0;

            duesData.forEach(due => {
                // Filters
                const searchMatch = (due.code || '').toLowerCase().includes(searchTerm) || 
                                    (due.party_id || '').toLowerCase().includes(searchTerm);
                const typeMatch = !filterType || due.type === filterType;
                const statusMatch = !filterStatus || due.status === filterStatus;

                if (!searchMatch || !typeMatch || !statusMatch) return;

                // KPI Calculation
                total++;
                if (due.status === 'Pending') pending++;
                if (due.status === 'Paid') paidAmt += parseFloat(due.amount || 0);
                if (due.type.includes('Salary')) salaryAmt += parseFloat(due.amount || 0);

                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${window.FleetmanFormatCreatedAt(due.created_at || due.createdAt)}</td>
                    <td>${due.code}</td>
                    <td><span class="badge" style="background:#f3f4f6;color:#374151;padding:4px 8px;border-radius:4px;font-size:12px;">${due.type}</span></td>
                    <td>${due.party_type}: <b>${due.party_id || 'N/A'}</b></td>
                    <td>${due.due_date || '-'}</td>
                    <td><b>৳ ${parseFloat(due.amount).toLocaleString('en-IN', {minimumFractionDigits: 2})}</b></td>
                    <td>
                        <span class="badge" style="padding:4px 8px;border-radius:4px;font-size:12px;background:${due.status==='Paid'?'#ecfdf5':'#fff7ed'};color:${due.status==='Paid'?'#065f46':'#9a3412'};border:1px solid ${due.status==='Paid'?'#a7f3d0':'#fed7aa'}">
                            ${due.status}
                        </span>
                    </td>
                    <td>
                        ${due.status === 'Pending' ? `<button class="btn light btn-sm mark-paid-btn" data-code="${due.code}">Mark as Paid</button>` : ''}
                    </td>
                `;
                tbody.appendChild(tr);
            });

            document.getElementById('dueKpiTotal').textContent = total;
            document.getElementById('dueKpiPending').textContent = pending;
            document.getElementById('dueKpiPaid').textContent = '৳ ' + paidAmt.toLocaleString();
            document.getElementById('dueKpiSalary').textContent = '৳ ' + salaryAmt.toLocaleString();

            document.querySelectorAll('.mark-paid-btn').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    const code = e.target.dataset.code;
                    markAsPaid(code);
                });
            });
        }

        const recordsUrl = "<?php echo e(route('fleet.dues.records')); ?>";
        const syncUrl = "<?php echo e(route('fleet.dues.sync')); ?>";
        const payrollUrl = "<?php echo e(route('fleet.dues.generate-payroll')); ?>";

        async function fetchDues() {
            try {
                const response = await fetch(recordsUrl, {
                    method: 'GET',
                    headers: { 'Accept': 'application/json' }
                });
                if (!response.ok) {
                    const errText = await response.text();
                    console.error('Server Error:', response.status, errText);
                    alert('Server Error ' + response.status + ': ' + errText.substring(0, 100));
                    return;
                }
                const res = await response.json();
                duesData = res.rows || [];
                renderDues();
            } catch (err) {
                console.error('Fetch exception:', err);
                alert('Error fetching dues. See console for details.');
            }
        }

        async function markAsPaid(code) {
            const due = duesData.find(d => d.code === code);
            if (!due) return;

            due.status = 'Paid';

            try {
                const response = await fetch(syncUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    body: JSON.stringify({ rows: [due] })
                });
                const res = await response.json();
                if (res.ok) {
                    duesData = res.rows || [];
                    renderDues();
                } else {
                    alert('Error marking as paid');
                }
            } catch (err) {
                console.error(err);
            }
        }

        function dhakaCalendarDate() {
            const parts = new Intl.DateTimeFormat('en-CA', {
                timeZone: 'Asia/Dhaka',
                year: 'numeric',
                month: '2-digit',
                day: '2-digit'
            }).formatToParts(new Date());
            const values = Object.fromEntries(parts.map(part => [part.type, part.value]));

            return {
                day: Number(values.day),
                month: `${values.year}-${values.month}`,
                display: `${values.day}-${values.month}-${values.year}`
            };
        }

        document.getElementById('generatePayrollBtn').addEventListener('click', async () => {
            const today = dhakaCalendarDate();

            if (today.day < 26 || today.day > 30) {
                alert(`Monthly payroll can only be generated from the 26th through the 30th of each month. Today is ${today.display}.`);
                return;
            }

            const enteredMonth = prompt('Enter the payroll month (YYYY-MM):', today.month);
            if (enteredMonth === null) return;

            const month = enteredMonth.trim();
            if (!/^\d{4}-(0[1-9]|1[0-2])$/.test(month)) {
                alert('Enter a valid payroll month using YYYY-MM format.');
                return;
            }

            if (month > today.month) {
                alert(`Future-month payroll cannot be generated. Select ${today.month} or an earlier month.`);
                return;
            }

            if (!confirm(`Generate and store payroll dues for ${month}? Existing records for this month will be preserved.`)) {
                return;
            }

            const btn = document.getElementById('generatePayrollBtn');
            btn.disabled = true;
            btn.textContent = 'Generating...';

            try {
                const response = await fetch(payrollUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ month })
                });
                const res = await response.json();

                if (response.ok && res.ok) {
                    alert(res.message);
                    duesData = res.rows || [];
                    renderDues();
                } else {
                    alert(res.message || 'Unable to generate payroll.');
                }
            } catch (err) {
                console.error(err);
                alert('Request failed. Please try again.');
            } finally {
                btn.disabled = false;
                btn.textContent = '🗓 Generate Monthly Payroll';
            }
        });

        document.getElementById('dueSearch').addEventListener('input', renderDues);
        document.getElementById('dueFilterType').addEventListener('change', renderDues);
        document.getElementById('dueFilterStatus').addEventListener('change', renderDues);
        document.getElementById('clearDueFiltersBtn').addEventListener('click', () => {
            document.getElementById('dueSearch').value = '';
            document.getElementById('dueFilterType').value = '';
            document.getElementById('dueFilterStatus').value = '';
            renderDues();
        });

        // Initial fetch
        fetchDues();
    });
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.fleetman', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Applications/XAMPP/xamppfiles/htdocs/laravel/FleetManagement/resources/views/fleetman/dues.blade.php ENDPATH**/ ?>