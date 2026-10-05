// Booking Core 前端應用程式互動邏輯

// ======================== 全域狀態 ========================
const state = {
    token: localStorage.getItem('bc_token') || null,
    user: null,
    services: [],
    slots: [],
    selectedService: null,
    selectedSlot: null,
    myAppointments: [],
    currentTab: 'book', // 'book' | 'my'
};

// ======================== API 請求封裝 ========================
async function api(endpoint, options = {}) {
    const headers = {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        ...(options.headers || {}),
    };

    if (state.token) {
        headers['Authorization'] = `Bearer ${state.token}`;
    }

    try {
        const response = await fetch(endpoint, {
            ...options,
            headers,
        });

        const data = await response.json().catch(() => ({}));

        if (response.status === 401 && state.token) {
            // Token 失效
            logout(false);
        }

        return {
            ok: response.ok,
            status: response.status,
            data,
        };
    } catch (err) {
        return {
            ok: false,
            status: 0,
            data: { message: '網路連線異常，請稍後再試。' },
        };
    }
}

// ======================== Toast 提示訊息 ========================
function showToast(message, type = 'info') {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const toast = document.createElement('div');
    const colorClasses = {
        success: 'bg-emerald-600 text-white',
        error: 'bg-rose-600 text-white',
        warning: 'bg-amber-600 text-white',
        info: 'bg-slate-900 text-white',
    }[type] || 'bg-slate-900 text-white';

    toast.className = `pointer-events-auto p-4 rounded-xl shadow-lg text-sm font-medium transition-all duration-300 transform translate-y-2 opacity-0 flex items-center justify-between gap-3 ${colorClasses}`;
    toast.innerHTML = `
        <span>${escapeHtml(message)}</span>
        <button class="opacity-70 hover:opacity-100 text-sm font-bold">✕</button>
    `;

    toast.querySelector('button').addEventListener('click', () => {
        toast.remove();
    });

    container.appendChild(toast);

    // 進場動畫
    requestAnimationFrame(() => {
        toast.classList.remove('translate-y-2', 'opacity-0');
    });

    // 3.5 秒後自動消失
    setTimeout(() => {
        toast.classList.add('opacity-0', '-translate-y-2');
        setTimeout(() => toast.remove(), 300);
    }, 3500);
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/[&<>"']/g, (m) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;',
    }[m]));
}

// ======================== 會員身分管理 ========================
async function checkAuth() {
    if (!state.token) {
        renderAuthSection();
        return;
    }

    const res = await api('/api/me');
    if (res.ok) {
        state.user = res.data;
    } else {
        state.token = null;
        localStorage.removeItem('bc_token');
        state.user = null;
    }

    renderAuthSection();
}

function renderAuthSection() {
    const container = document.getElementById('auth-section');
    const bookingTip = document.getElementById('booking-auth-tip');

    if (state.user) {
        container.innerHTML = `
            <div class="flex items-center gap-2">
                <span class="text-xs sm:text-sm font-medium text-slate-700 hidden sm:inline">
                    👤 <strong>${escapeHtml(state.user.name)}</strong>
                </span>
                <button id="btn-logout" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-200 text-slate-700 hover:bg-slate-300 transition">
                    登出
                </button>
            </div>
        `;

        document.getElementById('btn-logout').addEventListener('click', () => logout(true));
        if (bookingTip) bookingTip.classList.add('hidden');
    } else {
        container.innerHTML = `
            <button id="btn-open-login" class="px-3.5 py-1.5 rounded-lg text-sm font-semibold bg-indigo-600 text-white hover:bg-indigo-700 transition shadow-xs">
                登入 / 註冊
            </button>
        `;

        document.getElementById('btn-open-login').addEventListener('click', openAuthModal);
        if (bookingTip) bookingTip.classList.remove('hidden');
    }
}

async function logout(callApi = true) {
    if (callApi && state.token) {
        await api('/api/logout', { method: 'POST' });
    }

    state.token = null;
    state.user = null;
    localStorage.removeItem('bc_token');
    renderAuthSection();
    showToast('已安全登出。', 'info');

    if (state.currentTab === 'my') {
        renderMyAppointments();
    }
}

// ======================== 彈窗控制 ========================
function openAuthModal(mode = 'login') {
    const modal = document.getElementById('auth-modal');
    modal.classList.remove('hidden');
    switchAuthModalTab(mode);
}

function closeAuthModal() {
    const modal = document.getElementById('auth-modal');
    modal.classList.add('hidden');
}

function switchAuthModalTab(tab) {
    const tabLogin = document.getElementById('tab-modal-login');
    const tabReg = document.getElementById('tab-modal-register');
    const formLogin = document.getElementById('form-login');
    const formReg = document.getElementById('form-register');

    if (tab === 'login') {
        tabLogin.className = 'flex-1 pb-3 text-sm font-semibold text-indigo-600 border-b-2 border-indigo-600 transition';
        tabReg.className = 'flex-1 pb-3 text-sm font-semibold text-slate-400 hover:text-slate-600 transition';
        formLogin.classList.remove('hidden');
        formReg.classList.add('hidden');
    } else {
        tabReg.className = 'flex-1 pb-3 text-sm font-semibold text-indigo-600 border-b-2 border-indigo-600 transition';
        tabLogin.className = 'flex-1 pb-3 text-sm font-semibold text-slate-400 hover:text-slate-600 transition';
        formReg.classList.remove('hidden');
        formLogin.classList.add('hidden');
    }
}

// ======================== 資料載入與渲染 ========================
async function loadServices() {
    const loading = document.getElementById('services-loading');
    const container = document.getElementById('services-list');

    const res = await api('/api/services');
    if (loading) loading.classList.add('hidden');

    if (!res.ok) {
        container.innerHTML = `<div class="text-rose-500 text-sm">載入服務項目失敗：${escapeHtml(res.data.message)}</div>`;
        container.classList.remove('hidden');
        return;
    }

    state.services = res.data;
    container.innerHTML = '';
    container.classList.remove('hidden');

    if (state.services.length === 0) {
        container.innerHTML = '<div class="text-slate-400 text-sm">目前尚無服務項目。</div>';
        return;
    }

    state.services.forEach((service) => {
        const card = document.createElement('div');
        const isSelected = state.selectedService?.id === service.id;

        card.className = `p-5 rounded-xl border cursor-pointer transition-all duration-200 relative ${
            isSelected
                ? 'border-indigo-600 bg-indigo-50/50 shadow-sm ring-2 ring-indigo-500/20'
                : 'border-slate-200 bg-white hover:border-slate-300 hover:shadow-xs'
        }`;

        card.innerHTML = `
            <div class="flex items-start justify-between">
                <div>
                    <h4 class="font-bold text-slate-900 text-base">${escapeHtml(service.name)}</h4>
                    <p class="text-xs text-slate-500 mt-1">時長：約 ${service.duration_blocks * 30} 分鐘</p>
                </div>
                <span class="text-lg font-bold text-indigo-600">NT$ ${service.price}</span>
            </div>
            ${isSelected ? '<div class="absolute top-3 right-3 text-indigo-600 font-bold text-xs bg-indigo-100 px-2 py-0.5 rounded-full">✓ 已選取</div>' : ''}
        `;

        card.addEventListener('click', () => {
            state.selectedService = service;
            loadServices();
            updateSummary();
        });

        container.appendChild(card);
    });
}

async function loadSlots() {
    const loading = document.getElementById('slots-loading');
    const empty = document.getElementById('slots-empty');
    const container = document.getElementById('slots-list');

    if (loading) loading.classList.remove('hidden');
    if (empty) empty.classList.add('hidden');
    if (container) container.classList.add('hidden');

    const res = await api('/api/slots');
    if (loading) loading.classList.add('hidden');

    if (!res.ok) {
        if (empty) {
            empty.textContent = '載入時段失敗：' + (res.data.message || '請稍後再試');
            empty.classList.remove('hidden');
        }
        return;
    }

    state.slots = res.data;

    if (state.slots.length === 0) {
        if (empty) empty.classList.remove('hidden');
        return;
    }

    // 依日期分組
    const groups = {};
    state.slots.forEach((slot) => {
        const dateStr = slot.date.split('T')[0];
        if (!groups[dateStr]) groups[dateStr] = [];
        groups[dateStr].push(slot);
    });

    container.innerHTML = '';
    container.classList.remove('hidden');

    Object.keys(groups).sort().forEach((dateStr) => {
        const groupEl = document.createElement('div');
        groupEl.className = 'border border-slate-100 bg-slate-50/50 rounded-xl p-4';

        const dateHeader = document.createElement('h5');
        dateHeader.className = 'font-bold text-sm text-slate-800 mb-3 flex items-center gap-2';
        dateHeader.innerHTML = `📅 <span>${escapeHtml(dateStr)}</span>`;
        groupEl.appendChild(dateHeader);

        const slotsGrid = document.createElement('div');
        slotsGrid.className = 'grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 gap-2';

        groups[dateStr].forEach((slot) => {
            const btn = document.createElement('button');
            const isSelected = state.selectedSlot?.id === slot.id;
            const timeFormatted = slot.start_time.substring(0, 5);

            btn.type = 'button';
            btn.className = `py-2.5 px-3 rounded-lg text-sm font-semibold border transition ${
                isSelected
                    ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm ring-2 ring-indigo-200'
                    : 'bg-white text-slate-700 border-slate-200 hover:border-indigo-400 hover:text-indigo-600'
            }`;

            btn.textContent = timeFormatted;
            btn.addEventListener('click', () => {
                state.selectedSlot = slot;
                loadSlots();
                updateSummary();
            });

            slotsGrid.appendChild(btn);
        });

        groupEl.appendChild(slotsGrid);
        container.appendChild(groupEl);
    });
}

function updateSummary() {
    const summaryService = document.getElementById('summary-service');
    const summaryPrice = document.getElementById('summary-price');
    const summarySlot = document.getElementById('summary-slot');

    if (state.selectedService) {
        summaryService.textContent = state.selectedService.name;
        summaryPrice.textContent = `${state.selectedService.duration_blocks * 30} 分鐘 / NT$ ${state.selectedService.price}`;
    } else {
        summaryService.textContent = '尚未選擇';
        summaryPrice.textContent = '-';
    }

    if (state.selectedSlot) {
        const date = state.selectedSlot.date.split('T')[0];
        const time = state.selectedSlot.start_time.substring(0, 5);
        summarySlot.textContent = `${date} 於 ${time}`;
    } else {
        summarySlot.textContent = '尚未選擇';
    }
}

// ======================== 送出預約 ========================
async function handleBookingSubmit(e) {
    e.preventDefault();

    if (!state.user) {
        showToast('請先登入會員帳號後再進行預約。', 'warning');
        openAuthModal('login');
        return;
    }

    if (!state.selectedService) {
        showToast('請先於步驟一選擇服務項目。', 'warning');
        return;
    }

    if (!state.selectedSlot) {
        showToast('請先於步驟二選擇可預約時段。', 'warning');
        return;
    }

    const phone = document.getElementById('input-phone').value.trim();
    const note = document.getElementById('input-note').value.trim();

    const submitBtn = document.getElementById('btn-submit-booking');
    submitBtn.disabled = true;
    submitBtn.textContent = '預約處理中...';

    const res = await api('/api/appointments', {
        method: 'POST',
        body: JSON.stringify({
            service_id: state.selectedService.id,
            available_slot_id: state.selectedSlot.id,
            meta_data: {
                phone: phone || undefined,
                note: note || undefined,
            },
        }),
    });

    submitBtn.disabled = false;
    submitBtn.textContent = '確認立即預約';

    if (res.ok) {
        showToast('🎉 預約成功！已為您保留該時段。', 'success');
        state.selectedSlot = null;
        document.getElementById('input-phone').value = '';
        document.getElementById('input-note').value = '';
        updateSummary();
        await loadSlots();

        // 自動切換到我的預約
        switchTab('my');
    } else if (res.status === 409) {
        showToast('⚠️ 抱歉，這個時段剛剛已被搶先預約，請選擇其他時段！', 'error');
        state.selectedSlot = null;
        updateSummary();
        await loadSlots();
    } else {
        showToast(`預約失敗：${res.data.message || '請確認填寫內容'}`, 'error');
    }
}

// ======================== 我的預約清單 ========================
async function renderMyAppointments() {
    const unauthEl = document.getElementById('my-unauth');
    const container = document.getElementById('my-list-container');
    const loading = document.getElementById('my-loading');
    const empty = document.getElementById('my-empty');
    const items = document.getElementById('my-items');

    if (!state.user) {
        unauthEl.classList.remove('hidden');
        container.classList.add('hidden');
        return;
    }

    unauthEl.classList.add('hidden');
    container.classList.remove('hidden');
    loading.classList.remove('hidden');
    empty.classList.add('hidden');
    items.innerHTML = '';

    const res = await api('/api/appointments');
    loading.classList.add('hidden');

    if (!res.ok) {
        items.innerHTML = `<div class="text-rose-500 text-sm">查詢失敗：${escapeHtml(res.data.message)}</div>`;
        return;
    }

    state.myAppointments = res.data;

    if (state.myAppointments.length === 0) {
        empty.classList.remove('hidden');
        return;
    }

    state.myAppointments.forEach((apt) => {
        const item = document.createElement('div');
        const isCancelled = apt.status === 'cancelled';
        const dateStr = apt.available_slot ? apt.available_slot.date.split('T')[0] : '-';
        const timeStr = apt.available_slot ? apt.available_slot.start_time.substring(0, 5) : '-';
        const serviceName = apt.service ? apt.service.name : '預約服務';
        const price = apt.service ? `NT$ ${apt.service.price}` : '';

        const statusBadges = {
            pending: '<span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">待確認</span>',
            confirmed: '<span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">已確認</span>',
            cancelled: '<span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-500 line-through">已取消</span>',
        };

        item.className = `p-5 rounded-2xl border bg-white transition shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4 ${
            isCancelled ? 'border-slate-200 opacity-60' : 'border-slate-200 hover:border-slate-300'
        }`;

        item.innerHTML = `
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    <h4 class="font-bold text-slate-900 text-base">${escapeHtml(serviceName)}</h4>
                    ${statusBadges[apt.status] || ''}
                    <span class="text-xs text-slate-400 font-medium">${price}</span>
                </div>
                <div class="text-sm text-slate-600 flex items-center gap-3">
                    <span>📅 ${dateStr}</span>
                    <span>⏰ ${timeStr}</span>
                </div>
                ${apt.meta_data?.note ? `<div class="text-xs text-slate-500">備註：${escapeHtml(apt.meta_data.note)}</div>` : ''}
            </div>

            <div class="flex items-center gap-2">
                ${
                    !isCancelled
                        ? `<button data-id="${apt.id}" class="btn-cancel-apt px-4 py-2 rounded-lg text-xs font-semibold border border-rose-200 text-rose-600 hover:bg-rose-50 hover:border-rose-300 transition">
                            取消預約
                        </button>`
                        : '<span class="text-xs text-slate-400">已釋出時段</span>'
                }
            </div>
        `;

        items.appendChild(item);
    });

    // 綁定取消按鈕
    document.querySelectorAll('.btn-cancel-apt').forEach((btn) => {
        btn.addEventListener('click', async () => {
            const id = btn.getAttribute('data-id');
            if (!confirm('確定要取消這筆預約嗎？\n取消後該時段將立即釋出供他人預約。')) {
                return;
            }

            btn.disabled = true;
            btn.textContent = '取消中...';

            const cancelRes = await api(`/api/appointments/${id}/cancel`, {
                method: 'POST',
            });

            if (cancelRes.ok) {
                showToast('預約已成功取消，時段已釋出。', 'success');
                renderMyAppointments();
                loadSlots();
            } else {
                showToast(`取消失敗：${cancelRes.data.message || '請稍後再試'}`, 'error');
                btn.disabled = false;
                btn.textContent = '取消預約';
            }
        });
    });
}

// ======================== 分頁切換 ========================
function switchTab(tab) {
    state.currentTab = tab;
    const tabBookEl = document.getElementById('tab-book');
    const tabMyEl = document.getElementById('tab-my');
    const navBook = document.getElementById('nav-book-tab');
    const navMy = document.getElementById('nav-my-tab');

    if (tab === 'book') {
        tabBookEl.classList.remove('hidden');
        tabMyEl.classList.add('hidden');
        navBook.className = 'px-3.5 py-1.5 rounded-md transition-colors bg-white text-indigo-700 shadow-xs font-semibold';
        navMy.className = 'px-3.5 py-1.5 rounded-md transition-colors text-slate-600 hover:text-slate-900';
    } else {
        tabMyEl.classList.remove('hidden');
        tabBookEl.classList.add('hidden');
        navMy.className = 'px-3.5 py-1.5 rounded-md transition-colors bg-white text-indigo-700 shadow-xs font-semibold';
        navBook.className = 'px-3.5 py-1.5 rounded-md transition-colors text-slate-600 hover:text-slate-900';
        renderMyAppointments();
    }
}

// ======================== 事件綁定與初始化 ========================
document.addEventListener('DOMContentLoaded', () => {
    // 導航切換
    document.getElementById('nav-book-tab')?.addEventListener('click', () => switchTab('book'));
    document.getElementById('nav-my-tab')?.addEventListener('click', () => switchTab('my'));
    document.getElementById('btn-refresh-my')?.addEventListener('click', renderMyAppointments);
    document.getElementById('btn-my-login')?.addEventListener('click', () => openAuthModal('login'));

    // 時段重新整理
    document.getElementById('btn-refresh-slots')?.addEventListener('click', () => {
        loadSlots();
        showToast('時段已重新整理。', 'info');
    });

    // 預約表單提交
    document.getElementById('booking-form')?.addEventListener('submit', handleBookingSubmit);

    // Modal 控制
    document.getElementById('btn-close-modal')?.addEventListener('click', closeAuthModal);
    document.getElementById('tab-modal-login')?.addEventListener('click', () => switchAuthModalTab('login'));
    document.getElementById('tab-modal-register')?.addEventListener('click', () => switchAuthModalTab('register'));

    // 快速填入測試帳號
    document.getElementById('btn-quick-fill-test')?.addEventListener('click', () => {
        document.getElementById('login-email').value = 'test@example.com';
        document.getElementById('login-password').value = 'password';
    });

    // 登入表單
    document.getElementById('form-login')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const email = document.getElementById('login-email').value.trim();
        const password = document.getElementById('login-password').value;
        const btn = document.getElementById('btn-do-login');

        btn.disabled = true;
        btn.textContent = '登入中...';

        const res = await api('/api/login', {
            method: 'POST',
            body: JSON.stringify({ email, password }),
        });

        btn.disabled = false;
        btn.textContent = '登入';

        if (res.ok) {
            state.token = res.data.token;
            state.user = res.data.user;
            localStorage.setItem('bc_token', state.token);
            renderAuthSection();
            closeAuthModal();
            showToast(`歡迎回來，${state.user.name}！`, 'success');
            if (state.currentTab === 'my') renderMyAppointments();
        } else {
            showToast(res.data.message || '登入失敗，請確認帳號密碼。', 'error');
        }
    });

    // 註冊表單
    document.getElementById('form-register')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const name = document.getElementById('reg-name').value.trim();
        const email = document.getElementById('reg-email').value.trim();
        const password = document.getElementById('reg-password').value;
        const password_confirmation = document.getElementById('reg-password-confirm').value;
        const btn = document.getElementById('btn-do-register');

        btn.disabled = true;
        btn.textContent = '註冊中...';

        const res = await api('/api/register', {
            method: 'POST',
            body: JSON.stringify({ name, email, password, password_confirmation }),
        });

        btn.disabled = false;
        btn.textContent = '建立帳號並登入';

        if (res.ok) {
            state.token = res.data.token;
            state.user = res.data.user;
            localStorage.setItem('bc_token', state.token);
            renderAuthSection();
            closeAuthModal();
            showToast(`註冊成功，歡迎加入 ${state.user.name}！`, 'success');
            if (state.currentTab === 'my') renderMyAppointments();
        } else {
            showToast(res.data.message || '註冊失敗，請確認輸入資料。', 'error');
        }
    });

    // 初始化啟動
    checkAuth();
    loadServices();
    loadSlots();
});
