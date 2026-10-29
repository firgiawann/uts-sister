// Global Application State
let currentAssignments = [];
let activeCourseFilter = 'ALL';
let currentViewMode = 'cards'; // 'cards' or 'table'
let currentRole = 'MAHASISWA'; // 'MAHASISWA' or 'DOSEN'

document.addEventListener('DOMContentLoaded', () => {
    loadConfig();
    fetchAssignments();
    triggerHealthCheck();

    // Default deadline to 7 days ahead
    const nextWeek = new Date();
    nextWeek.setDate(nextWeek.getDate() + 7);
    nextWeek.setMinutes(nextWeek.getMinutes() - nextWeek.getTimezoneOffset());
    document.getElementById('create-due-date').value = nextWeek.toISOString().slice(0, 16);
});

// 1. TAB NAVIGATION
function switchTab(tabId) {
    document.querySelectorAll('.tab-pane').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.nav-tab').forEach(el => el.classList.remove('active'));

    const targetPane = document.getElementById(tabId);
    if (targetPane) targetPane.classList.add('active');

    // Highlight active nav tab
    const tabMap = {
        'tab-assignments': 0,
        'tab-courses': 1,
        'tab-network': 2,
        'tab-console': 3
    };
    const tabIdx = tabMap[tabId];
    if (tabIdx !== undefined) {
        document.querySelectorAll('.nav-tab')[tabIdx]?.classList.add('active');
    }
}

// 2. ROLE TOGGLE (Mahasiswa <-> Dosen)
function toggleRole() {
    if (currentRole === 'MAHASISWA') {
        currentRole = 'DOSEN';
        document.getElementById('role-icon').innerText = '👨‍🏫';
        document.getElementById('role-title').innerText = 'Dosen Pengampu';
        document.getElementById('role-title').style.color = '#a5b4fc';
        document.querySelector('.role-sub').innerText = 'Mode Evaluasi / Kelola Tugas';
    } else {
        currentRole = 'MAHASISWA';
        document.getElementById('role-icon').innerText = '👨‍🎓';
        document.getElementById('role-title').innerText = 'Mahasiswa';
        document.getElementById('role-title').style.color = 'white';
        document.querySelector('.role-sub').innerText = 'Klik ganti Dosen';
    }
    renderCards(filterList(currentAssignments));
    renderTable(filterList(currentAssignments));
}

// 3. VIEW TOGGLE (Cards vs Table)
function setViewMode(mode) {
    currentViewMode = mode;
    const cardsEl = document.getElementById('assignments-cards-container');
    const tableEl = document.getElementById('assignments-table-container');
    const btnCards = document.getElementById('btn-view-cards');
    const btnTable = document.getElementById('btn-view-table');

    if (mode === 'cards') {
        cardsEl.style.display = 'grid';
        tableEl.style.display = 'none';
        btnCards.classList.add('active');
        btnTable.classList.remove('active');
    } else {
        cardsEl.style.display = 'none';
        tableEl.style.display = 'block';
        btnCards.classList.remove('active');
        btnTable.classList.add('active');
    }
}

// 4. COURSE FILTERING
function filterByCourse(courseCode) {
    activeCourseFilter = courseCode;

    // Update active style on filter chips
    document.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
    if (courseCode === 'ALL') {
        document.querySelector('.filter-chip')?.classList.add('active');
    } else {
        const clsMap = {
            'PTIK301': '.chip-course-301',
            'PTIK302': '.chip-course-302',
            'PTIK303': '.chip-course-303'
        };
        const target = document.querySelector(clsMap[courseCode]);
        if (target) target.classList.add('active');
    }

    switchTab('tab-assignments');
    const filtered = filterList(currentAssignments);
    renderCards(filtered);
    renderTable(filtered);
}

function filterList(items) {
    const search = (document.getElementById('search-input')?.value || '').toLowerCase().trim();
    return items.filter(it => {
        if (activeCourseFilter !== 'ALL' && strcasecmp(it.course_id, activeCourseFilter) !== 0) {
            return false;
        }
        if (search) {
            const title = (it.title || '').toLowerCase();
            const desc = (it.description || '').toLowerCase();
            const cname = (it.course_name || '').toLowerCase();
            if (!title.includes(search) && !desc.includes(search) && !cname.includes(search)) {
                return false;
            }
        }
        return true;
    });
}

function strcasecmp(a, b) {
    return (a || '').toLowerCase() === (b || '').toLowerCase();
}

// 5. FETCH & RENDER ASSIGNMENTS
async function fetchAssignments() {
    try {
        const res = await fetch('/api/assignments');
        const data = await res.json();
        currentAssignments = data.data || [];

        // Update stats
        const statAll = document.getElementById('stat-all-count');
        if (statAll) statAll.innerText = `${currentAssignments.length} Tugas Tersedia`;

        const filtered = filterList(currentAssignments);
        renderCards(filtered);
        renderTable(filtered);
    } catch (err) {
        document.getElementById('assignments-cards-container').innerHTML = `
            <div class="loading-state" style="color: #f43f5e;">Gagal memuat tugas: ${err.message}</div>
        `;
    }
}

function renderCards(items) {
    const container = document.getElementById('assignments-cards-container');
    if (!items || items.length === 0) {
        container.innerHTML = `
            <div class="loading-state" style="grid-column: 1 / -1;">
                <p>Tidak ada tugas untuk filter ini.</p>
                <button class="btn btn-sm btn-primary" style="margin-top: 10px;" onclick="filterByCourse('ALL')">Tampilkan Semua Tugas</button>
            </div>
        `;
        return;
    }

    container.innerHTML = items.map(it => {
        const cCode = (it.course_id || 'PTIK301').toUpperCase();
        let tagClass = 'tag-ptik301';
        if (cCode === 'PTIK302') tagClass = 'tag-ptik302';
        if (cCode === 'PTIK303') tagClass = 'tag-ptik303';

        return `
            <div class="lms-assignment-card">
                <div>
                    <div class="card-top-bar">
                        <span class="course-tag ${tagClass}">${escapeHtml(it.course_id)}</span>
                        <span class="status-badge ${it.status === 'OPEN' ? 'open' : 'closed'}">${it.status}</span>
                    </div>

                    <div class="card-title-group" style="margin-top: 12px;">
                        <h3>${escapeHtml(it.title)}</h3>
                        <p class="card-desc">${escapeHtml(it.description || 'Tidak ada petunjuk tambahan.')}</p>
                    </div>
                </div>

                <div>
                    <div class="card-meta-list">
                        <div class="meta-row">
                            <span class="meta-label">📚 Mata Kuliah</span>
                            <span class="meta-val">${escapeHtml(it.course_name || 'Sistem Terdistribusi')}</span>
                        </div>
                        <div class="meta-row">
                            <span class="meta-label">⏳ Batas Waktu</span>
                            <span class="meta-val" style="color: #cbd5e1;">${escapeHtml(it.due_date)}</span>
                        </div>
                        <div class="meta-row">
                            <span class="meta-label">🏆 Bobot Nilai</span>
                            <span class="meta-val" style="color: #a5b4fc;">${it.max_points} Poin</span>
                        </div>
                        <div class="meta-row">
                            <span class="meta-label">🌐 Layanan Simpan</span>
                            <span class="meta-val" style="color: var(--accent-cyan); font-size: 0.72rem;">Kelompok 5 (Submission)</span>
                        </div>
                    </div>

                    <div class="card-action-bar">
                        <button class="btn btn-primary btn-sm" style="flex-grow: 1;" onclick="viewSubmissions('${it.id}', '${it.course_id}')">
                            📤 Kumpulkan & Cek Submissions
                        </button>
                        <button class="btn btn-secondary btn-sm" title="Lihat Response JSON" onclick="testEndpoint('GET', '/api/assignments/${it.id}')">
                            Detail
                        </button>
                        ${currentRole === 'DOSEN' ? `
                            <button class="btn btn-icon delete" title="Hapus Tugas (Mode Dosen)" onclick="deleteAssignment('${it.id}')">
                                🗑️
                            </button>
                        ` : ''}
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

function renderTable(items) {
    const tbody = document.getElementById('assignment-table-body');
    if (!items || items.length === 0) {
        tbody.innerHTML = `<tr><td colspan="7" class="loading-cell">Tidak ada tugas ditemukan.</td></tr>`;
        return;
    }

    tbody.innerHTML = items.map(it => `
        <tr>
            <td style="font-family: var(--font-code); color: #a5b4fc;"><strong>${escapeHtml(it.id)}</strong></td>
            <td>
                <strong>${escapeHtml(it.course_id)}</strong>
                <div style="font-size: 0.75rem; color: var(--text-muted);">${escapeHtml(it.course_name || '')}</div>
            </td>
            <td>
                <div style="font-weight: 600; color: white;">${escapeHtml(it.title)}</div>
            </td>
            <td style="font-size: 0.8rem; color: var(--text-secondary);">${escapeHtml(it.due_date)}</td>
            <td><strong style="color: #a5b4fc;">${it.max_points}</strong></td>
            <td><span class="status-badge ${it.status === 'OPEN' ? 'open' : 'closed'}">${it.status}</span></td>
            <td>
                <div class="action-btn-group">
                    <button class="btn-icon" onclick="viewSubmissions('${it.id}', '${it.course_id}')">Submissions</button>
                    <button class="btn-icon" onclick="testEndpoint('GET', '/api/assignments/${it.id}')">Detail</button>
                    <button class="btn-icon delete" onclick="deleteAssignment('${it.id}')">Hapus</button>
                </div>
            </td>
        </tr>
    `).join('');
}

// 6. CREATE ASSIGNMENT (DOSEN MODE)
function showCreateModal() {
    document.getElementById('create-modal').classList.add('show');
}

function closeCreateModal() {
    document.getElementById('create-modal').classList.remove('show');
}

async function submitCreateAssignment(e) {
    e.preventDefault();
    const selectCourse = document.getElementById('create-course-id');
    const courseId = selectCourse.value;
    const courseText = selectCourse.options[selectCourse.selectedIndex].text;
    const courseName = courseText.includes('—') 
        ? courseText.split('—')[1].replace(/\(.*\)/, '').trim() 
        : (courseText.split('-')[1]?.replace(/\(.*\)/, '').trim() || 'Sistem Terdistribusi');

    const payload = {
        course_id: courseId,
        course_name: courseName,
        title: document.getElementById('create-title').value.trim(),
        description: document.getElementById('create-description').value.trim(),
        due_date: document.getElementById('create-due-date').value.replace('T', ' ') + ':00',
        max_points: parseInt(document.getElementById('create-points').value, 10),
        status: 'OPEN'
    };

    try {
        const res = await fetch('/api/assignments', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        logResponse('POST', '/api/assignments', data);

        closeCreateModal();
        document.getElementById('create-assignment-form').reset();
        alert('Tugas berhasil dibuat dan divalidasi ke Kelompok 3!');
        fetchAssignments();
    } catch (err) {
        alert('Gagal membuat tugas: ' + err.message);
    }
}

// 7. DELETE ASSIGNMENT
async function deleteAssignment(id) {
    if (!confirm(`Hapus tugas '${id}'? (Akan mengeksekusi DELETE /api/assignments/${id})`)) {
        return;
    }

    try {
        const res = await fetch(`/api/assignments/${id}`, { method: 'DELETE' });
        const data = await res.json();
        logResponse('DELETE', `/api/assignments/${id}`, data);
        fetchAssignments();
    } catch (err) {
        alert('Gagal menghapus tugas: ' + err.message);
    }
}

// 8. SUBMISSIONS & FILE UPLOAD (KELOMPOK 5 INTEGRATION)
async function viewSubmissions(id, courseId) {
    document.getElementById('sub-modal-title').innerText = `Pengumpulan untuk Tugas: ${id}`;
    document.getElementById('sub-assignment-id').value = id;
    const badgeEl = document.getElementById('modal-course-badge');
    if (badgeEl) badgeEl.innerText = courseId || 'PTIK301';

    document.getElementById('sub-modal-body').innerHTML = `
        <p class="loading-cell">Menghubungi Kelompok 5 (${document.getElementById('cfg-k5')?.value || 'Port 8105'})...</p>
    `;
    document.getElementById('submissions-modal').classList.add('show');
    refreshSubmissionList(id);
}

async function refreshSubmissionList(id) {
    try {
        const res = await fetch(`/api/assignments/${id}/submissions`);
        const data = await res.json();
        logResponse('GET', `/api/assignments/${id}/submissions`, data);

        const intData = data.kelompok_5_integration || {};
        const subs = intData.submissions || [];
        const source = intData.source || 'UNKNOWN';

        let html = `
            <div style="margin-bottom: 14px; padding: 10px 14px; background: rgba(30, 41, 59, 0.7); border-radius: 8px; border: 1px solid rgba(99, 102, 241, 0.25); font-size: 0.82rem;">
                <strong>Sumber Data:</strong> <code>${escapeHtml(source)}</code><br>
                <small style="color: var(--text-secondary);">${escapeHtml(intData.note || 'Data pengumpulan tugas diambil dari Kelompok 5 Submission Service.')}</small>
            </div>
        `;

        if (!subs || subs.length === 0) {
            html += `<p style="text-align: center; color: var(--text-muted); padding: 24px;">Belum ada mahasiswa yang mengumpulkan berkas untuk tugas ini.</p>`;
        } else {
            html += subs.map(s => `
                <div class="submission-card">
                    <div class="submission-meta">
                        <h5>${escapeHtml(s.student_name || 'Mahasiswa')} <span style="font-family: var(--font-code); color: #818cf8;">(${escapeHtml(s.student_id || '-')})</span></h5>
                        <p>ID: ${escapeHtml(s.submission_id || s.id)} • Dikumpulkan: ${escapeHtml(s.submitted_at || '-')}</p>
                        <p style="font-family: var(--font-code); color: var(--accent-cyan); font-size: 0.75rem; margin-top: 4px;">
                            📂 ${escapeHtml(s.file_name || s.file_path || '-')}
                        </p>
                    </div>
                    <div>
                        <span class="status-badge ${s.status === 'GRADED' ? 'closed' : 'open'}">${escapeHtml(s.status || 'TERKUMPUL')}</span>
                    </div>
                </div>
            `).join('');
        }

        document.getElementById('sub-modal-body').innerHTML = html;
    } catch (err) {
        document.getElementById('sub-modal-body').innerHTML = `
            <p style="color: #f43f5e; padding: 20px; text-align: center;">Error menghubungi Kelompok 5: ${err.message}</p>
        `;
    }
}

async function submitStudentWork(e) {
    e.preventDefault();
    const assignmentId = document.getElementById('sub-assignment-id').value;
    const studentId = document.getElementById('sub-student-id').value.trim();
    const studentName = document.getElementById('sub-student-name').value.trim();
    const fileInput = document.getElementById('sub-file');

    if (!fileInput.files || fileInput.files.length === 0) {
        alert('Silakan pilih berkas tugas yang ingin diunggah!');
        return;
    }

    const formData = new FormData();
    formData.append('student_id', studentId);
    formData.append('student_name', studentName);
    formData.append('assignment_id', assignmentId);
    formData.append('file', fileInput.files[0]);

    try {
        const res = await fetch(`/api/assignments/${assignmentId}/submissions`, {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        logResponse('POST', `/api/assignments/${assignmentId}/submissions (K5)`, data);
        alert('✅ Berhasil mengirim berkas ke Kelompok 5!\n(Kelompok 5 secara otomatis memicu notifikasi ke Kelompok 6)');
        document.getElementById('upload-submission-form').reset();
        refreshSubmissionList(assignmentId);
    } catch (err) {
        alert('Gagal mengirim ke Kelompok 5: ' + err.message);
    }
}

function closeSubmissionsModal() {
    document.getElementById('submissions-modal').classList.remove('show');
}

// 9. LIVE COURSES FROM KELOMPOK 3
async function fetchLiveCoursesFromK3() {
    try {
        const res = await fetch('/api/external/courses');
        const data = await res.json();
        logResponse('GET', '/api/external/courses (Kelompok 3)', data);

        const list = data.data || data;
        if (Array.isArray(list) && list.length > 0) {
            // Update dropdown in create modal
            const selectEl = document.getElementById('create-course-id');
            selectEl.innerHTML = list.map(c => `
                <option value="${escapeHtml(c.code)}">${escapeHtml(c.code)} — ${escapeHtml(c.name)} (${c.credits || 3} SKS)</option>
            `).join('');

            // Update course catalog cards
            const catalogContainer = document.getElementById('courses-catalog-container');
            if (catalogContainer) {
                catalogContainer.innerHTML = list.map(c => `
                    <div class="course-card">
                        <div class="course-header">
                            <span class="badge badge-group">${escapeHtml(c.code)}</span>
                            <span class="badge-credits">${c.credits || 3} SKS</span>
                        </div>
                        <h3>${escapeHtml(c.name)}</h3>
                        <p class="course-desc">Mata kuliah resmi terdaftar di database Kelompok 3 Course Service.</p>
                        <div class="course-footer">
                            <span class="sync-status online">● Terverifikasi Kelompok 3</span>
                            <button class="btn btn-sm btn-outline" onclick="filterByCourse('${escapeHtml(c.code)}')">Lihat Tugas</button>
                        </div>
                    </div>
                `).join('');
            }

            alert(`Berhasil sinkronisasi ${list.length} mata kuliah dari Kelompok 3!`);
        }
    } catch (err) {
        alert('Gagal menghubungi Kelompok 3: ' + err.message);
    }
}

// 10. CONFIGURATION & HEALTH CHECK
async function loadConfig() {
    try {
        const res = await fetch('/api/config');
        const data = await res.json();
        if (data.status === 'success') {
            const cfg = data.config;
            document.getElementById('cfg-k1').value = cfg.kelompok_1_gateway_url || 'http://127.0.0.1:8101';
            document.getElementById('cfg-k3').value = cfg.kelompok_3_course_url || 'http://10.194.169.45:8103';
            document.getElementById('cfg-k5').value = cfg.kelompok_5_submission_url || 'http://127.0.0.1:8105';
            document.getElementById('cfg-mock').checked = Boolean(cfg.use_mock_fallback);

            document.getElementById('ip-k1').innerText = cfg.kelompok_1_gateway_url || 'http://127.0.0.1:8101';
            document.getElementById('ip-k3').innerText = cfg.kelompok_3_course_url || 'http://10.194.169.45:8103';
            document.getElementById('ip-k5').innerText = cfg.kelompok_5_submission_url || 'http://127.0.0.1:8105';
        }
    } catch (err) {
        console.error('Gagal memuat konfigurasi:', err);
    }
}

async function saveConfig(e) {
    if (e) e.preventDefault();
    const payload = {
        kelompok_1_gateway_url: document.getElementById('cfg-k1').value.trim(),
        kelompok_3_course_url: document.getElementById('cfg-k3').value.trim(),
        kelompok_5_submission_url: document.getElementById('cfg-k5').value.trim(),
        use_mock_fallback: document.getElementById('cfg-mock').checked
    };

    try {
        const res = await fetch('/api/config', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        logResponse('POST', '/api/config', data);
        alert('IP Perangkat tersimpan dengan sukses!');
        loadConfig();
        triggerHealthCheck();
    } catch (err) {
        alert('Gagal menyimpan: ' + err.message);
    }
}

async function triggerHealthCheck() {
    try {
        const res = await fetch('/health');
        const data = await res.json();
        logResponse('GET', '/health', data);

        if (data.dependencies) {
            updateBadge('badge-k1', data.dependencies.kelompok_1_api_gateway.status);
            updateBadge('badge-k3', data.dependencies.kelompok_3_course.status);
            updateBadge('badge-k5', data.dependencies.kelompok_5_submission.status);
        }
    } catch (err) {
        logResponse('GET', '/health', { error: 'Server tidak merespons: ' + err.message });
    }
}

function updateBadge(badgeId, status) {
    const el = document.getElementById(badgeId);
    if (!el) return;
    if (status === 'ONLINE') {
        el.className = 'badge-mini active';
        el.innerText = 'ONLINE';
    } else {
        el.className = 'badge-mini wait';
        el.innerText = 'STANDBY';
    }
}

async function pingTarget(group) {
    await saveConfig(null);
    triggerHealthCheck();
}

async function pingAllServices() {
    await saveConfig(null);
    triggerHealthCheck();
}

// 11. GENERIC API TESTING HELPER
async function testEndpoint(method, url) {
    switchTab('tab-console');
    try {
        const res = await fetch(url, { method });
        const data = await res.json();
        logResponse(method, url, data);
    } catch (err) {
        logResponse(method, url, { error: err.message });
    }
}

function logResponse(method, url, data) {
    document.getElementById('last-method-pill').innerText = method;
    document.getElementById('endpoint-url-display').innerText = `${method} ${url}`;
    document.getElementById('json-output').innerText = JSON.stringify(data, null, 2);
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
