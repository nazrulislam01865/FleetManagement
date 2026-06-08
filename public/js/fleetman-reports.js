(function () {
    'use strict';

    const fleetman = window.FLEETMAN || {};
    const report = fleetman.report || {};
    const page = document.querySelector('[data-report-page]')?.dataset.reportPage || report.type;
    if (!page) return;

    const records = Array.isArray(report.records) ? report.records : [];
    let filtered = [];
    let currentPage = 1;
    let pageSize = 10;
    let summaryRows = [];

    const $ = (selector, root = document) => root.querySelector(selector);
    const $$ = (selector, root = document) => Array.from(root.querySelectorAll(selector));
    const value = (selector) => $(selector)?.value || '';
    const setText = (selector, text) => { const node = $(selector); if (node) node.textContent = text; };
    const num = (value) => Number(value || 0);
    const money = (value) => '৳ ' + num(value).toLocaleString('en-BD', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const tkPerKm = (totalCost, totalKm) => num(totalKm) > 0 ? (num(totalCost) / Math.max(num(totalKm), 1)) : 0;
    const escapeHtml = (value) => String(value ?? '').replace(/[&<>'"]/g, (ch) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' }[ch]));

    function uniqueSorted(items) {
        return [...new Set((items || []).filter(Boolean).map((item) => String(item)))].sort((left, right) => left.localeCompare(right));
    }

    function fillSearchableInput(id, items, options = {}) {
        const input = $('#' + id);
        const list = $('#' + (options.listId || `${id}List`));
        if (!input || !list) return;

        const unique = uniqueSorted(items);
        const current = String(input.value || '');
        list.innerHTML = unique.map((item) => `<option value="${escapeHtml(item)}"></option>`).join('');

        if (options.clearInvalid && current && !unique.includes(current)) {
            input.value = '';
        }
    }

    function fillSearchableOptions(id, items, selectedValue = '') {
        const input = $('#' + id);
        const list = $('#' + `${id}List`);
        if (!input || !list) return;

        const normalizedItems = (items || []).filter((item) => item && item.value && item.label);
        list.innerHTML = normalizedItems.map((item) => `<option value="${escapeHtml(item.label)}"></option>`).join('');
        const selected = normalizedItems.find((item) => item.value === selectedValue || item.label === selectedValue) || normalizedItems[0];
        input.value = selected?.label || '';
    }

    function reportDate(value) {
        const date = new Date(String(value) + 'T00:00:00');
        if (Number.isNaN(date.getTime())) return value || '-';
        return date.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }).replace(/ /g, '-');
    }

    function shortDate(value) {
        const date = new Date(String(value) + 'T00:00:00');
        if (Number.isNaN(date.getTime())) return value || '-';
        return date.toLocaleDateString('en-GB', { weekday: 'short', day: '2-digit', month: 'short' }).replace(',', '');
    }

    function monthLabel(monthValue) {
        const found = (report.months || []).find((month) => month.value === monthValue);
        if (found) return found.label;
        const date = new Date(monthValue + '-01T00:00:00');
        return Number.isNaN(date.getTime()) ? monthValue : date.toLocaleDateString('en-GB', { month: 'long', year: 'numeric' });
    }

    function selectedPageRows() {
        pageSize = Number(value('#pageSize') || 10);
        const start = (currentPage - 1) * pageSize;
        return summaryRows.slice(start, start + pageSize);
    }

    function renderPagination() {
        const pages = Math.max(1, Math.ceil(summaryRows.length / pageSize));
        if (currentPage > pages) currentPage = pages;
        const from = summaryRows.length ? ((currentPage - 1) * pageSize + 1) : 0;
        const to = Math.min(currentPage * pageSize, summaryRows.length);
        setText('#pageInfo', `Showing ${from} - ${to} of ${summaryRows.length} ${page === 'monthly' ? 'summary rows' : 'rows'}`);
        const pageNumbers = $('#pageNumbers');
        if (pageNumbers) {
            pageNumbers.innerHTML = Array.from({ length: pages }, (_, index) => {
                const pageNo = index + 1;
                return `<button class="mini-btn ${currentPage === pageNo ? 'active' : ''}" type="button" data-report-page-no="${pageNo}">${pageNo}</button>`;
            }).join('');
        }
    }

    function baseRecordFilter(row) {
        const contract = value('#contractFilter');
        const vehicle = value('#vehicleFilter');
        const driver = value('#driverFilter');
        const status = value('#statusFilter');
        return (!contract || row.contract === contract)
            && (!vehicle || row.car === vehicle)
            && (!driver || row.driver === driver)
            && (!status || row.status === status);
    }

    function filterDailyRecords() {
        const from = value('#fromDate');
        const to = value('#toDate');
        const fuel = value('#fuelFilter');
        return records.filter((row) => (!from || row.date >= from)
            && (!to || row.date <= to)
            && baseRecordFilter(row)
            && (!fuel || row.fuelType === fuel));
    }

    function selectedWeek() {
        const selected = value('#weekFilter');
        return (report.weeks || []).find((week) => week.value === selected || week.label === selected) || (report.weeks || [])[0] || null;
    }

    function selectedMonth() {
        const selected = value('#monthFilter');
        return (report.months || []).find((month) => month.value === selected || month.label === selected) || (report.months || [])[0] || null;
    }

    function refreshContractDependentFilters(clearInvalid = true) {
        const contract = value('#contractFilter');
        const matchingRows = contract ? records.filter((row) => row.contract === contract) : records;
        fillSearchableInput('vehicleFilter', matchingRows.map((row) => row.car), { clearInvalid });
        fillSearchableInput('driverFilter', matchingRows.map((row) => row.driver), { clearInvalid });
    }

    function isValidContractSearchValue() {
        const contract = value('#contractFilter');
        return !contract || uniqueSorted((report.filters || {}).contracts || []).includes(contract);
    }

    function groupKey(row) {
        return [row.contract, row.car, row.driver].join('||');
    }

    function groupRows(rows, days = null, prefix = 'SUM') {
        const groups = new Map();
        rows.forEach((row) => {
            const key = groupKey(row);
            if (!groups.has(key)) groups.set(key, []);
            groups.get(key).push(row);
        });

        return Array.from(groups.entries()).map(([key, group], index) => {
            const ordered = group.slice().sort((a, b) => String(a.date).localeCompare(String(b.date)) || String(a.entryId).localeCompare(String(b.entryId)));
            const [contract, car, driver] = key.split('||');
            const totalDiesel = sum(ordered, 'diesel');
            const totalGas = sum(ordered, 'gas');
            const totalOctane = sum(ordered, 'octane');
            const totalKm = sum(ordered, 'totalKm');
            const totalCost = sum(ordered, 'totalCost');
            const totalTime = sum(ordered, 'totalTime');
            const startKm = ordered[0]?.startKm || 0;
            const endKm = ordered[ordered.length - 1]?.endKm || 0;
            const activeDates = new Set(ordered.map((row) => row.date));
            const status = ordered.some((row) => row.status === 'Draft') ? 'Draft' : (ordered[ordered.length - 1]?.status || 'Submitted');
            const submittedBy = ordered[ordered.length - 1]?.submittedBy || '-';

            return {
                summaryId: `${prefix}-${String(index + 1).padStart(4, '0')}`,
                contract, car, driver,
                rows: ordered,
                daily: (days || []).map((day) => {
                    const dayRows = ordered.filter((row) => row.date === day.date);
                    return {
                        date: day.date,
                        label: day.label || shortDate(day.date),
                        diesel: sum(dayRows, 'diesel'),
                        gas: sum(dayRows, 'gas'),
                        octane: sum(dayRows, 'octane'),
                    };
                }),
                activeDays: activeDates.size,
                totalTime,
                totalDiesel,
                totalGas,
                totalOctane,
                startKm,
                endKm,
                totalKm,
                totalCost,
                tkKm: tkPerKm(totalCost, totalKm),
                avgMileage: totalKm / Math.max(totalDiesel + totalOctane, 1),
                status,
                submittedBy,
            };
        });
    }

    function sum(rows, key) {
        return rows.reduce((total, row) => total + num(row[key]), 0);
    }

    function applyReport() {
        currentPage = 1;
        if (page === 'daily') renderDaily();
        if (page === 'weekly') renderWeekly();
        if (page === 'monthly') renderMonthly();
    }

    function renderDaily() {
        filtered = filterDailyRecords();
        summaryRows = filtered;
        const rows = selectedPageRows();
        const tbody = $('#tbody');
        if (tbody) {
            tbody.innerHTML = rows.length ? rows.map((row) => `<tr>
                <td class="sticky-left sticky-1">${escapeHtml(row.entryId)}</td>
                <td class="sticky-left sticky-2">${reportDate(row.date)}</td>
                <td class="sticky-left sticky-3">${escapeHtml(row.contract)}</td>
                <td class="sticky-left sticky-4">${escapeHtml(row.car)}</td>
                <td>${escapeHtml(row.driver)}</td>
                <td class="bordered-cell">${escapeHtml(row.driverStart)}</td>
                <td class="bordered-cell">${escapeHtml(row.driverEnd)}</td>
                <td class="bordered-cell">${num(row.totalTime).toFixed(2)}</td>
                <td class="bordered-cell">${num(row.diesel).toFixed(2)}</td>
                <td class="bordered-cell">${money(row.gas)}</td>
                <td class="bordered-cell">${num(row.octane).toFixed(2)}</td>
                <td class="bordered-cell">${num(row.startKm).toFixed(0)}</td>
                <td class="bordered-cell">${num(row.endKm).toFixed(0)}</td>
                <td class="bordered-cell">${num(row.totalKm).toFixed(0)}</td>
                <td class="bordered-cell">${money(row.tkKm || tkPerKm(row.totalCost, row.totalKm))}</td>
                <td class="bordered-cell">${num(row.mileage).toFixed(2)}</td>
                <td><span class="badge ${row.status === 'Submitted' ? 'ok' : 'warn'}">${escapeHtml(row.status)}</span></td>
                <td>${escapeHtml(row.submittedBy)}</td>
            </tr>`).join('') : '<tr><td colspan="18" class="empty">No report rows found for the selected filters.</td></tr>';
        }
        renderDailyCards(rows);
        renderPagination();
        updateDailyKpis(filtered);
    }

    function renderDailyCards(rows) {
        const mobileCards = $('#mobileCards');
        if (!mobileCards) return;
        mobileCards.innerHTML = rows.map((row) => `<div class="report-card">
            <h3>${escapeHtml(row.entryId)} <span class="badge ${row.status === 'Submitted' ? 'ok' : 'warn'}">${escapeHtml(row.status)}</span></h3>
            <div><b>${reportDate(row.date)}</b> • ${escapeHtml(row.contract)} • ${escapeHtml(row.car)} • ${escapeHtml(row.driver)}</div>
            <div class="mini-grid">
                <div><b>Work Time</b><br>${escapeHtml(row.driverStart)} - ${escapeHtml(row.driverEnd)}<br>${num(row.totalTime).toFixed(2)} hrs</div>
                <div><b>Total KM</b><br>${num(row.totalKm).toFixed(0)}</div>
                <div><b>Tk(KM)</b><br>${money(row.tkKm || tkPerKm(row.totalCost, row.totalKm))}</div>
                <div><b>Diesel</b><br>${num(row.diesel).toFixed(2)} L</div>
                <div><b>Gas Cost</b><br>${money(row.gas)}</div>
                <div><b>Octane</b><br>${num(row.octane).toFixed(2)} L</div>
                <div><b>Mileage</b><br>${num(row.mileage).toFixed(2)} KM/L</div>
                <div><b>ODO</b><br>${num(row.startKm).toFixed(0)} → ${num(row.endKm).toFixed(0)}</div>
                <div><b>Submitted By</b><br>${escapeHtml(row.submittedBy)}</div>
            </div>
        </div>`).join('');
    }

    function updateDailyKpis(rows) {
        setText('#kpiRows', rows.length);
        setText('#kpiHours', sum(rows, 'totalTime').toFixed(2));
        setText('#kpiDiesel', sum(rows, 'diesel').toFixed(2));
        setText('#kpiGas', money(sum(rows, 'gas')));
        setText('#kpiKm', sum(rows, 'totalKm').toFixed(0));
        setText('#kpiDraft', rows.filter((row) => row.status === 'Draft').length);
    }

    function renderWeekly() {
        const week = selectedWeek();
        if (!week) return;
        const rows = records.filter((row) => row.date >= week.start && row.date <= week.end && baseRecordFilter(row));
        filtered = rows;
        summaryRows = groupRows(rows, week.days, 'WFR');
        buildWeeklyHeader(week);
        const pageRows = selectedPageRows();
        const tbody = $('#tbody');
        if (tbody) {
            tbody.innerHTML = pageRows.length ? pageRows.map((row) => `<tr>
                <td class="sticky-left sticky-1">${escapeHtml(row.summaryId)}</td>
                <td class="sticky-left sticky-2">${escapeHtml(row.contract)}</td>
                <td class="sticky-left sticky-3">${escapeHtml(row.car)}</td>
                <td class="sticky-left sticky-4">${escapeHtml(row.driver)}</td>
                <td style="text-align:right">${num(row.totalTime).toFixed(2)}</td>
                ${row.daily.map((day) => `<td class="date-cell">${num(day.diesel).toFixed(2)}</td><td class="date-cell">${money(day.gas)}</td><td class="date-cell">${num(day.octane).toFixed(2)}</td>`).join('')}
                <td style="text-align:right">${num(row.totalDiesel).toFixed(2)}</td>
                <td style="text-align:right">${money(row.totalGas)}</td>
                <td style="text-align:right">${num(row.totalOctane).toFixed(2)}</td>
                <td>${num(row.startKm).toFixed(0)}</td>
                <td>${num(row.endKm).toFixed(0)}</td>
                <td>${num(row.totalKm).toFixed(0)}</td>
                <td>${money(row.tkKm)}</td>
                <td>${num(row.avgMileage).toFixed(2)}</td>
                <td><span class="badge ${row.status === 'Submitted' ? 'ok' : 'warn'}">${escapeHtml(row.status)}</span></td>
                <td>${escapeHtml(row.submittedBy)}</td>
            </tr>`).join('') : '<tr><td colspan="33" class="empty">No weekly report rows found for the selected filters.</td></tr>';
        }
        renderSummaryCards(pageRows);
        renderPagination();
        updateWeeklyKpis(summaryRows);
    }

    function buildWeeklyHeader(week) {
        const thead = $('#thead');
        if (!thead) return;
        thead.innerHTML = `<tr>
            <th rowspan="2" class="sticky-left sticky-1">Entry ID</th>
            <th rowspan="2" class="sticky-left sticky-2">Contract</th>
            <th rowspan="2" class="sticky-left sticky-3">Car</th>
            <th rowspan="2" class="sticky-left sticky-4">Driver</th>
            <th rowspan="2">Total Time</th>
            ${week.days.map((day) => `<th colspan="3" class="date-group">${escapeHtml(day.label)}</th>`).join('')}
            <th rowspan="2">Total Diesel</th>
            <th rowspan="2">Total Gas Cost</th>
            <th rowspan="2">Total Octane</th>
            <th rowspan="2">Start KM</th>
            <th rowspan="2">End KM</th>
            <th rowspan="2">Total KM</th>
            <th rowspan="2">Tk(KM)</th>
            <th rowspan="2">Mileage</th>
            <th rowspan="2">Status</th>
            <th rowspan="2">Submitted By</th>
        </tr><tr>${week.days.map(() => '<th class="date-sub">Diesel (L)</th><th class="date-sub">CNG/LPG Cost (৳)</th><th class="date-sub">Octane (L)</th>').join('')}</tr>`;
    }

    function updateWeeklyKpis(rows) {
        setText('#kpiRows', rows.length);
        setText('#kpiHours', sum(rows, 'totalTime').toFixed(2));
        setText('#kpiDiesel', sum(rows, 'totalDiesel').toFixed(2));
        setText('#kpiGas', money(sum(rows, 'totalGas')));
        setText('#kpiOctane', sum(rows, 'totalOctane').toFixed(2));
        setText('#kpiKm', sum(rows, 'totalKm').toFixed(0));
    }

    function renderMonthly() {
        const month = selectedMonth();
        if (!month) return;
        const rows = records.filter((row) => row.date >= month.start && row.date <= month.end && baseRecordFilter(row));
        filtered = rows;
        const days = Array.from({ length: Number(month.days || 0) }, (_, index) => {
            const day = String(index + 1).padStart(2, '0');
            const date = `${month.value}-${day}`;
            return { date, label: shortDate(date) };
        });
        summaryRows = groupRows(rows, days, 'MFR').map((row) => ({ ...row, month: month.value, monthLabel: month.label }));
        const pageRows = selectedPageRows();
        const tbody = $('#tbody');
        if (tbody) {
            tbody.innerHTML = pageRows.length ? pageRows.map((row) => `<tr>
                <td class="sticky-left sticky-1">${escapeHtml(row.summaryId)}</td>
                <td class="sticky-left sticky-2">${escapeHtml(row.monthLabel)}</td>
                <td class="sticky-left sticky-3">${escapeHtml(row.contract)}</td>
                <td class="sticky-left sticky-4">${escapeHtml(row.car)}</td>
                <td>${escapeHtml(row.driver)}</td>
                <td class="bordered-cell">${row.activeDays}</td>
                <td class="bordered-cell">${num(row.totalTime).toFixed(2)}</td>
                <td class="bordered-cell">${num(row.totalDiesel).toFixed(2)}</td>
                <td class="bordered-cell">${money(row.totalGas)}</td>
                <td class="bordered-cell">${num(row.totalOctane).toFixed(2)}</td>
                <td class="bordered-cell">${num(row.startKm).toFixed(0)}</td>
                <td class="bordered-cell">${num(row.endKm).toFixed(0)}</td>
                <td class="bordered-cell">${num(row.totalKm).toFixed(0)}</td>
                <td class="bordered-cell">${money(row.tkKm)}</td>
                <td class="bordered-cell">${num(row.avgMileage).toFixed(2)}</td>
                <td><span class="badge ${row.status === 'Submitted' ? 'ok' : 'warn'}">${escapeHtml(row.status)}</span></td>
                <td>${escapeHtml(row.submittedBy)}</td>
            </tr>`).join('') : '<tr><td colspan="17" class="empty">No monthly summary rows found for the selected filters.</td></tr>';
        }
        renderMonthlyCards(pageRows);
        renderPagination();
        updateMonthlyKpis(summaryRows);
    }

    function renderSummaryCards(rows) {
        const mobileCards = $('#mobileCards');
        if (!mobileCards) return;
        mobileCards.innerHTML = rows.map((row) => `<div class="report-card">
            <h3>${escapeHtml(row.summaryId)} <span class="badge ${row.status === 'Submitted' ? 'ok' : 'warn'}">${escapeHtml(row.status)}</span></h3>
            <div><b>${escapeHtml(row.contract)}</b> • ${escapeHtml(row.car)} • ${escapeHtml(row.driver)}</div>
            <div class="mini-grid">
                <div><b>Hours</b><br>${num(row.totalTime).toFixed(2)}</div>
                <div><b>Total KM</b><br>${num(row.totalKm).toFixed(0)}</div>
                <div><b>Tk(KM)</b><br>${money(row.tkKm || tkPerKm(row.totalCost, row.totalKm))}</div>
                <div><b>Diesel</b><br>${num(row.totalDiesel).toFixed(2)} L</div>
                <div><b>Gas Cost</b><br>${money(row.totalGas)}</div>
                <div><b>Octane</b><br>${num(row.totalOctane).toFixed(2)} L</div>
                <div><b>Mileage</b><br>${num(row.avgMileage).toFixed(2)}</div>
            </div>
        </div>`).join('');
    }

    function renderMonthlyCards(rows) {
        const mobileCards = $('#mobileCards');
        if (!mobileCards) return;
        mobileCards.innerHTML = rows.map((row) => `<div class="report-card">
            <h3>${escapeHtml(row.summaryId)} <span class="badge ${row.status === 'Submitted' ? 'ok' : 'warn'}">${escapeHtml(row.status)}</span></h3>
            <div><b>${escapeHtml(row.monthLabel)}</b> • ${escapeHtml(row.contract)} • ${escapeHtml(row.car)} • ${escapeHtml(row.driver)}</div>
            <div class="mini-grid">
                <div><b>Active Days</b><br>${row.activeDays}</div>
                <div><b>Total Hours</b><br>${num(row.totalTime).toFixed(2)}</div>
                <div><b>Diesel</b><br>${num(row.totalDiesel).toFixed(2)} L</div>
                <div><b>Gas Cost</b><br>${money(row.totalGas)}</div>
                <div><b>Octane</b><br>${num(row.totalOctane).toFixed(2)} L</div>
                <div><b>Total KM</b><br>${num(row.totalKm).toFixed(0)}</div>
                <div><b>Tk(KM)</b><br>${money(row.tkKm)}</div>
            </div>
        </div>`).join('');
    }

    function updateMonthlyKpis(rows) {
        setText('#kpiRows', rows.length);
        setText('#kpiDays', rows.reduce((total, row) => total + row.activeDays, 0));
        setText('#kpiHours', sum(rows, 'totalTime').toFixed(2));
        setText('#kpiDiesel', sum(rows, 'totalDiesel').toFixed(2));
        setText('#kpiGas', money(sum(rows, 'totalGas')));
        setText('#kpiKm', sum(rows, 'totalKm').toFixed(0));
    }

    function resetFilters() {
        ['contractFilter', 'vehicleFilter', 'driverFilter', 'statusFilter', 'fuelFilter'].forEach((id) => {
            const node = $('#' + id);
            if (node) node.value = '';
        });
        const pageSizeNode = $('#pageSize');
        if (pageSizeNode) pageSizeNode.value = '10';
        if (page === 'daily') {
            $('#fromDate').value = report.defaults?.fromDate || report.dateRange?.min || '';
            $('#toDate').value = report.defaults?.toDate || report.dateRange?.max || '';
        }
        if (page === 'weekly' && $('#weekFilter')) fillSearchableOptions('weekFilter', report.weeks || [], report.defaults?.week);
        if (page === 'monthly' && $('#monthFilter')) fillSearchableOptions('monthFilter', report.months || [], report.defaults?.month);
        refreshContractDependentFilters(false);
        applyReport();
    }

    function csvRows() {
        if (page === 'daily') {
            return [
                ['Entry ID', 'Date', 'Contract', 'Car', 'Driver', 'Driver Start', 'Driver End', 'Total Time (hrs)', 'Diesel (L)', 'CNG/LPG Cost', 'Octane (L)', 'Start KM', 'End KM', 'Total KM', 'Tk(KM)', 'Mileage (KM/L)', 'Draft/Submitted', 'Submitted By'],
                ...filtered.map((row) => [row.entryId, reportDate(row.date), row.contract, row.car, row.driver, row.driverStart, row.driverEnd, row.totalTime, row.diesel, row.gas, row.octane, row.startKm, row.endKm, row.totalKm, row.tkKm || tkPerKm(row.totalCost, row.totalKm), row.mileage, row.status, row.submittedBy]),
            ];
        }
        if (page === 'weekly') {
            const week = selectedWeek();
            return [
                ['Entry ID', 'Contract', 'Car', 'Driver', 'Total Time', ...week.days.flatMap((day) => [`${day.label} Diesel (L)`, `${day.label} CNG/LPG Cost`, `${day.label} Octane (L)`]), 'Total Diesel', 'Total Gas Cost', 'Total Octane', 'Start KM', 'End KM', 'Total KM', 'Tk(KM)', 'Mileage', 'Status', 'Submitted By'],
                ...summaryRows.map((row) => [row.summaryId, row.contract, row.car, row.driver, row.totalTime, ...row.daily.flatMap((day) => [day.diesel, day.gas, day.octane]), row.totalDiesel, row.totalGas, row.totalOctane, row.startKm, row.endKm, row.totalKm, row.tkKm, row.avgMileage, row.status, row.submittedBy]),
            ];
        }
        return monthlySummaryRows();
    }

    function monthlySummaryRows() {
        return [
            ['Summary ID', 'Month', 'Contract', 'Car', 'Driver', 'Active Days', 'Total Hours', 'Diesel (L)', 'CNG/LPG Cost (BDT)', 'Octane (L)', 'Month Start KM', 'Month End KM', 'Total KM', 'Tk(KM)', 'Avg Mileage', 'Last Status', 'Submitted By'],
            ...summaryRows.map((row) => [row.summaryId, row.monthLabel, row.contract, row.car, row.driver, row.activeDays, row.totalTime, row.totalDiesel, row.totalGas, row.totalOctane, row.startKm, row.endKm, row.totalKm, row.tkKm, row.avgMileage, row.status, row.submittedBy]),
        ];
    }

    function exportCsv() {
        const rows = csvRows();
        const csv = rows.map((row) => row.map((cell) => `"${String(cell ?? '').replaceAll('"', '""')}"`).join(',')).join('\n');
        download(csv, `${page}-driver-fuel-report.csv`, 'text/csv;charset=utf-8;');
    }

    function exportExcel() {
        showProgress('Preparing Excel export...', () => {
            const rows = csvRows();
            const html = '<table border="1">' + rows.map((row, rowIndex) => '<tr>' + row.map((cell) => rowIndex === 0 ? `<th>${escapeHtml(cell)}</th>` : `<td>${escapeHtml(cell)}</td>`).join('') + '</tr>').join('') + '</table>';
            download(html, `${page}-driver-fuel-report.xls`, 'application/vnd.ms-excel');
        });
    }

    function exportMonthlyDatewiseExcel() {
        const month = selectedMonth();
        if (!month) return;
        showProgress('Preparing date-wise monthly Excel...', () => {
            const days = Array.from({ length: Number(month.days || 0) }, (_, index) => {
                const date = `${month.value}-${String(index + 1).padStart(2, '0')}`;
                return { date, label: shortDate(date) };
            });
            let html = `<html><head><meta charset="UTF-8"></head><body><table border="1" style="border-collapse:collapse;font-family:Arial;font-size:12px;">`;
            html += `<tr style="background:#bfe8f7;font-weight:bold;"><td colspan="${5 + days.length * 3 + 9}">Filters</td></tr>`;
            html += `<tr><td>Month</td><td>${escapeHtml(month.label)}</td><td>Contract</td><td>${escapeHtml(value('#contractFilter') || 'All')}</td><td>Car</td><td>${escapeHtml(value('#vehicleFilter') || 'All')}</td><td>Driver</td><td>${escapeHtml(value('#driverFilter') || 'All')}</td><td>Status</td><td>${escapeHtml(value('#statusFilter') || 'All')}</td></tr>`;
            html += `<tr><td colspan="${5 + days.length * 3 + 9}">&nbsp;</td></tr>`;
            html += `<tr style="background:#184f7d;color:#fff;font-weight:bold;text-align:center;">`;
            ['Entry ID', 'Contract', 'Car', 'Driver', 'Total Time (hrs)'].forEach((head) => { html += `<td rowspan="2">${head}</td>`; });
            days.forEach((day) => { html += `<td colspan="3" style="border-left:3px solid #053e63;border-right:3px solid #053e63;">${escapeHtml(day.label)}</td>`; });
            ['Total Diesel (L)', 'Total CNG/LPG Cost', 'Total Octane (L)', 'Start KM', 'End KM', 'Total KM', 'Tk(KM)', 'Mileage (KM/L)', 'Submitted By'].forEach((head) => { html += `<td rowspan="2">${head}</td>`; });
            html += `</tr><tr style="background:#dff4ff;color:#16324f;font-weight:bold;text-align:center;">`;
            days.forEach(() => { html += `<td>Diesel (L)</td><td>CNG/LPG Cost</td><td>Octane (L)</td>`; });
            html += `</tr>`;
            summaryRows.forEach((row) => {
                html += `<tr>`;
                [row.summaryId, row.contract, row.car, row.driver, num(row.totalTime).toFixed(2)].forEach((cell) => { html += `<td>${escapeHtml(cell)}</td>`; });
                row.daily.forEach((day) => { html += `<td style="text-align:right;border-left:2px solid #a6d9f5;">${num(day.diesel).toFixed(2)}</td><td style="text-align:right;">${num(day.gas).toFixed(2)}</td><td style="text-align:right;border-right:2px solid #a6d9f5;">${num(day.octane).toFixed(2)}</td>`; });
                [num(row.totalDiesel).toFixed(2), num(row.totalGas).toFixed(2), num(row.totalOctane).toFixed(2), row.startKm, row.endKm, row.totalKm, money(row.tkKm), num(row.avgMileage).toFixed(2), row.submittedBy].forEach((cell) => { html += `<td style="text-align:right;">${escapeHtml(cell)}</td>`; });
                html += `</tr>`;
            });
            html += `<tr style="font-weight:bold;background:#eef7ff;"><td colspan="4">Total</td><td>${sum(summaryRows, 'totalTime').toFixed(2)}</td>`;
            days.forEach((day, index) => {
                const diesel = summaryRows.reduce((total, row) => total + num(row.daily[index]?.diesel), 0).toFixed(2);
                const gas = summaryRows.reduce((total, row) => total + num(row.daily[index]?.gas), 0).toFixed(2);
                const octane = summaryRows.reduce((total, row) => total + num(row.daily[index]?.octane), 0).toFixed(2);
                html += `<td style="text-align:right;">${diesel}</td><td style="text-align:right;">${gas}</td><td style="text-align:right;">${octane}</td>`;
            });
            html += `<td>${sum(summaryRows, 'totalDiesel').toFixed(2)}</td><td>${sum(summaryRows, 'totalGas').toFixed(2)}</td><td>${sum(summaryRows, 'totalOctane').toFixed(2)}</td><td></td><td></td><td>${sum(summaryRows, 'totalKm').toFixed(0)}</td><td>${money(tkPerKm(sum(summaryRows, 'totalCost'), sum(summaryRows, 'totalKm')))}</td><td></td><td></td></tr>`;
            html += `</table></body></html>`;
            download(html, `${month.label.replace(/\s+/g, '-').toLowerCase()}-driver-fuel-date-wise-monthly-report.xls`, 'application/vnd.ms-excel');
        });
    }

    function showProgress(message, callback) {
        const wrap = $('#progressWrap');
        const bar = $('#barInner');
        const text = $('#progressText');
        if (!wrap || !bar || !text) {
            callback();
            return;
        }
        wrap.style.display = 'block';
        bar.style.width = '0%';
        text.textContent = `${message} 0%`;
        let progress = 0;
        const timer = window.setInterval(() => {
            progress += 20;
            bar.style.width = progress + '%';
            text.textContent = `${message} ${progress}%`;
            if (progress >= 100) {
                window.clearInterval(timer);
                callback();
                window.setTimeout(() => { text.textContent = 'Export file is ready and downloaded.'; }, 250);
            }
        }, 120);
    }

    function download(content, filename, mimeType) {
        const link = document.createElement('a');
        link.href = URL.createObjectURL(new Blob([content], { type: mimeType }));
        link.download = filename;
        link.click();
        URL.revokeObjectURL(link.href);
    }

    function init() {
        const filters = report.filters || {};
        fillSearchableInput('contractFilter', filters.contracts || []);
        fillSearchableInput('vehicleFilter', filters.vehicles || []);
        fillSearchableInput('driverFilter', filters.drivers || []);
        fillSearchableInput('statusFilter', filters.statuses || []);
        fillSearchableInput('fuelFilter', filters.fuelTypes || []);
        if ($('#fromDate')) $('#fromDate').value = report.defaults?.fromDate || report.dateRange?.min || '';
        if ($('#toDate')) $('#toDate').value = report.defaults?.toDate || report.dateRange?.max || '';
        fillSearchableOptions('weekFilter', report.weeks || [], report.defaults?.week);
        fillSearchableOptions('monthFilter', report.months || [], report.defaults?.month);
        refreshContractDependentFilters(false);
        bindEvents();
        applyReport();
    }

    function bindEvents() {
        $('[data-report-apply]')?.addEventListener('click', applyReport);
        $('[data-report-reset]')?.addEventListener('click', resetFilters);
        $('#pageSize')?.addEventListener('change', () => { currentPage = 1; applyReport(); });
        $('#contractFilter')?.addEventListener('input', () => {
            if (!isValidContractSearchValue()) return;
            refreshContractDependentFilters(true);
            applyReport();
        });
        $('#contractFilter')?.addEventListener('change', () => {
            refreshContractDependentFilters(true);
            applyReport();
        });
        ['#fromDate', '#toDate', '#vehicleFilter', '#driverFilter', '#statusFilter', '#fuelFilter', '#weekFilter', '#monthFilter'].forEach((selector) => {
            $(selector)?.addEventListener('change', applyReport);
        });
        $('.report-prev-page')?.addEventListener('click', () => {
            if (currentPage > 1) {
                currentPage -= 1;
                if (page === 'daily') renderDaily();
                if (page === 'weekly') renderWeekly();
                if (page === 'monthly') renderMonthly();
            }
        });
        $('.report-next-page')?.addEventListener('click', () => {
            const pages = Math.max(1, Math.ceil(summaryRows.length / pageSize));
            if (currentPage < pages) {
                currentPage += 1;
                if (page === 'daily') renderDaily();
                if (page === 'weekly') renderWeekly();
                if (page === 'monthly') renderMonthly();
            }
        });
        document.addEventListener('click', (event) => {
            const pageButton = event.target.closest('[data-report-page-no]');
            if (pageButton) {
                currentPage = Number(pageButton.dataset.reportPageNo || 1);
                if (page === 'daily') renderDaily();
                if (page === 'weekly') renderWeekly();
                if (page === 'monthly') renderMonthly();
            }
            const exportButton = event.target.closest('[data-report-export]');
            if (exportButton) {
                const type = exportButton.dataset.reportExport;
                if (type === 'csv') exportCsv();
                if (type === 'excel') exportExcel();
                if (type === 'monthly-datewise-excel') exportMonthlyDatewiseExcel();
            }
        });
    }

    document.addEventListener('DOMContentLoaded', init);
})();
