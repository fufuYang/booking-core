<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Booking Core | 線上通用預約系統</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen flex flex-col font-sans">
    <!-- 頂部導航列 -->
    <header class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-xs">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center font-bold text-xl shadow-sm">
                    BC
                </div>
                <div>
                    <h1 class="font-bold text-lg text-slate-900 leading-tight">Booking Core</h1>
                    <p class="text-xs text-slate-500">通用預約管理系統</p>
                </div>
            </div>

            <!-- 導航分頁 -->
            <div class="flex items-center gap-2">
                <nav class="flex items-center gap-1 bg-slate-100 p-1 rounded-lg text-sm font-medium">
                    <button id="nav-book-tab" class="px-3.5 py-1.5 rounded-md transition-colors bg-white text-indigo-700 shadow-xs font-semibold">
                        預約服務
                    </button>
                    <button id="nav-my-tab" class="px-3.5 py-1.5 rounded-md transition-colors text-slate-600 hover:text-slate-900">
                        我的預約
                    </button>
                </nav>

                <!-- 會員登入 / 狀態 -->
                <div id="auth-section" class="ml-2 sm:ml-4 flex items-center gap-2">
                    <!-- 載入中骨架或動態渲染 -->
                    <button id="btn-open-login" class="px-3.5 py-1.5 rounded-lg text-sm font-medium bg-slate-900 text-white hover:bg-slate-800 transition">
                        登入 / 註冊
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- 訊息提示 (Toast) -->
    <div id="toast-container" class="fixed top-20 right-4 z-50 flex flex-col gap-2 max-w-sm w-full pointer-events-none"></div>

    <!-- 主要內容區塊 -->
    <main class="flex-1 max-w-6xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- ======================= 預約流程分頁 ======================= -->
        <section id="tab-book" class="space-y-8">
            <!-- 標題與簡介 -->
            <div class="bg-gradient-to-r from-indigo-900 to-slate-900 text-white rounded-2xl p-6 sm:p-8 shadow-sm">
                <span class="inline-block px-3 py-1 bg-indigo-500/30 text-indigo-200 text-xs font-semibold rounded-full mb-3">線上預約</span>
                <h2 class="text-2xl sm:text-3xl font-bold tracking-tight">挑選服務與合適時段</h2>
                <p class="text-indigo-200/80 text-sm sm:text-base mt-2 max-w-2xl">
                    三步驟輕鬆完成：先挑選服務項目，再選擇未預約的熱門時段，填寫備註立即確認。
                </p>
            </div>

            <!-- 步驟一：選擇服務項目 -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs">
                <div class="flex items-center gap-3 mb-4">
                    <span class="w-7 h-7 rounded-full bg-indigo-100 text-indigo-700 font-bold flex items-center justify-center text-sm">1</span>
                    <h3 class="text-lg font-bold text-slate-900">選擇服務項目</h3>
                </div>
                <div id="services-loading" class="text-slate-400 text-sm py-4">正在載入服務項目...</div>
                <div id="services-list" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 hidden"></div>
            </div>

            <!-- 步驟二：選擇時段 -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <span class="w-7 h-7 rounded-full bg-indigo-100 text-indigo-700 font-bold flex items-center justify-center text-sm">2</span>
                        <h3 class="text-lg font-bold text-slate-900">選擇可預約時段</h3>
                    </div>
                    <button id="btn-refresh-slots" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium flex items-center gap-1">
                        🔄 重新整理時段
                    </button>
                </div>
                <div id="slots-loading" class="text-slate-400 text-sm py-4">正在載入可用時段...</div>
                <div id="slots-empty" class="text-slate-400 text-sm py-4 hidden">目前沒有可預約的時段。</div>
                <div id="slots-list" class="space-y-4 hidden"></div>
            </div>

            <!-- 步驟三：確認預約與填寫聯絡資料 -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs">
                <div class="flex items-center gap-3 mb-4">
                    <span class="w-7 h-7 rounded-full bg-indigo-100 text-indigo-700 font-bold flex items-center justify-center text-sm">3</span>
                    <h3 class="text-lg font-bold text-slate-900">預約確認與備註</h3>
                </div>

                <div id="booking-summary" class="bg-slate-50 border border-slate-200 rounded-xl p-4 mb-5 text-sm">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-slate-700">
                        <div><span class="text-slate-400">已選服務：</span><strong id="summary-service" class="text-slate-900">尚未選擇</strong></div>
                        <div><span class="text-slate-400">時長 / 費用：</span><span id="summary-price" class="text-slate-900">-</span></div>
                        <div><span class="text-slate-400">預約時段：</span><strong id="summary-slot" class="text-slate-900">尚未選擇</strong></div>
                    </div>
                </div>

                <form id="booking-form" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">聯絡電話</label>
                            <input type="text" id="input-phone" placeholder="例如：0912345678" class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 outline-none transition" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">預約備註需求</label>
                            <input type="text" id="input-note" placeholder="例如：第一次諮詢、指定項目等" class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 outline-none transition" />
                        </div>
                    </div>

                    <div class="pt-2 flex flex-col sm:flex-row items-center justify-between gap-3">
                        <div id="booking-auth-tip" class="text-xs text-amber-600 flex items-center gap-1 hidden">
                            ⚠️ 預約前請先登入會員帳號。
                        </div>
                        <button type="submit" id="btn-submit-booking" class="w-full sm:w-auto px-6 py-2.5 bg-indigo-600 text-white font-semibold text-sm rounded-lg hover:bg-indigo-700 transition shadow-sm disabled:opacity-50 disabled:cursor-not-allowed">
                            確認立即預約
                        </button>
                    </div>
                </form>
            </div>
        </section>

        <!-- ======================= 我的預約紀錄分頁 ======================= -->
        <section id="tab-my" class="space-y-6 hidden">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-slate-900 tracking-tight">我的預約紀錄</h2>
                    <p class="text-sm text-slate-500">查看您過去與未來的預約，並可於需要時釋出時段取消。</p>
                </div>
                <button id="btn-refresh-my" class="px-3 py-1.5 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 rounded-lg text-xs font-medium transition shadow-xs">
                    重新載入
                </button>
            </div>

            <!-- 未登入提示 -->
            <div id="my-unauth" class="bg-white border border-slate-200 rounded-2xl p-10 text-center space-y-3">
                <div class="text-4xl">🔒</div>
                <h3 class="text-lg font-bold text-slate-900">請先登入帳號</h3>
                <p class="text-sm text-slate-500 max-w-sm mx-auto">登入後即可即時查詢您的預約歷史、預約狀態或取消預約。</p>
                <button id="btn-my-login" class="px-5 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">
                    立即登入
                </button>
            </div>

            <!-- 預約清單 -->
            <div id="my-list-container" class="space-y-3 hidden">
                <div id="my-loading" class="text-slate-400 text-sm py-4">正在查詢您的預約...</div>
                <div id="my-empty" class="bg-white border border-slate-200 rounded-2xl p-8 text-center text-slate-400 text-sm hidden">
                    目前尚無任何預約紀錄。
                </div>
                <div id="my-items" class="space-y-3"></div>
            </div>
        </section>
    </main>

    <!-- ======================= 登入 / 註冊彈窗 (Modal) ======================= -->
    <div id="auth-modal" class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4 hidden">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-100 relative">
            <button id="btn-close-modal" class="absolute top-4 right-4 text-slate-400 hover:text-slate-600 text-lg leading-none">
                ✕
            </button>

            <!-- Modal 頂部分頁切換 -->
            <div class="flex border-b border-slate-200 mb-6">
                <button id="tab-modal-login" class="flex-1 pb-3 text-sm font-semibold text-indigo-600 border-b-2 border-indigo-600 transition">
                    會員登入
                </button>
                <button id="tab-modal-register" class="flex-1 pb-3 text-sm font-semibold text-slate-400 hover:text-slate-600 transition">
                    註冊新帳號
                </button>
            </div>

            <!-- 登入表單 -->
            <form id="form-login" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">電子郵件 (Email)</label>
                    <input type="email" id="login-email" required placeholder="name@example.com" class="w-full px-3.5 py-2 rounded-lg border border-slate-300 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 outline-none transition" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">密碼</label>
                    <input type="password" id="login-password" required placeholder="••••••••" class="w-full px-3.5 py-2 rounded-lg border border-slate-300 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 outline-none transition" />
                </div>
                <button type="submit" id="btn-do-login" class="w-full py-2.5 bg-indigo-600 text-white font-semibold text-sm rounded-lg hover:bg-indigo-700 transition">
                    登入
                </button>
                <div class="pt-2 text-center">
                    <button type="button" id="btn-quick-fill-test" class="text-xs text-indigo-600 hover:underline">
                        填入測試帳號 (test@example.com / password)
                    </button>
                </div>
            </form>

            <!-- 註冊表單 -->
            <form id="form-register" class="space-y-4 hidden">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">姓名 / 暱稱</label>
                    <input type="text" id="reg-name" required placeholder="小明" class="w-full px-3.5 py-2 rounded-lg border border-slate-300 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 outline-none transition" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">電子郵件 (Email)</label>
                    <input type="email" id="reg-email" required placeholder="name@example.com" class="w-full px-3.5 py-2 rounded-lg border border-slate-300 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 outline-none transition" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">密碼（至少 8 碼）</label>
                    <input type="password" id="reg-password" required placeholder="••••••••" class="w-full px-3.5 py-2 rounded-lg border border-slate-300 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 outline-none transition" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">確認密碼</label>
                    <input type="password" id="reg-password-confirm" required placeholder="••••••••" class="w-full px-3.5 py-2 rounded-lg border border-slate-300 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 outline-none transition" />
                </div>
                <button type="submit" id="btn-do-register" class="w-full py-2.5 bg-indigo-600 text-white font-semibold text-sm rounded-lg hover:bg-indigo-700 transition">
                    建立帳號並登入
                </button>
            </form>
        </div>
    </div>

    <!-- 頁尾 -->
    <footer class="bg-white border-t border-slate-200 mt-auto py-6">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-xs text-slate-400">
            Booking Core 預約系統 · Laravel 13 + Docker + Tailwind CSS
        </div>
    </footer>
</body>
</html>
