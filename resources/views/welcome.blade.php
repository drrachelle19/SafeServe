
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SafeServe | Dashboard</title>

    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/lucide@latest"></script>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --forest: #10251f;
            --green: #174c3a;
            --emerald: #39a879;
            --bg: #f5f6f3;
            --white: #ffffff;
            --text: #202824;
            --muted: #87918a;
            --border: #e9ede8;
            --red: #dc5757;
            --amber: #d99a32;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--bg);
            color: var(--text);
        }

        .layout {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 250px;
            background: var(--forest);
            color: white;
            padding: 30px 18px;
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0 12px 38px;
        }

        .brand-icon {
            width: 42px;
            height: 42px;
            background: var(--emerald);
            border-radius: 13px;
            display: grid;
            place-items: center;
            font-size: 23px;
        }

        .brand h2 {
            font-size: 21px;
            letter-spacing: -1px;
        }

        .brand span {
            color: #79cba7;
        }

        .nav-label {
            color: #82968c;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 1.5px;
            padding: 0 13px;
            margin: 18px 0 12px;
        }

        .nav-link {
            text-decoration: none;
            color: #bdcbc3;
            padding: 13px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 13px;
            margin-bottom: 5px;
            font-size: 13px;
            font-weight: 500;
            transition: .2s;
        }

        .nav-link:hover, .nav-link.active {
            background: #204a3b;
            color: white;
        }

        .nav-link.active {
            border-left: 3px solid #49bd8a;
        }

        .sidebar-bottom {
            margin-top: auto;
            border-top: 1px solid #294137;
            padding: 20px 10px 0;
        }

        .user {
            display: flex;
            align-items: center;
            gap: 11px;
        }

        .avatar {
            width: 38px;
            height: 38px;
            background: #34785c;
            border-radius: 50%;
            display: grid;
            place-items: center;
            font-weight: 700;
        }

        .user small {
            display: block;
            color: #9aada3;
            margin-top: 4px;
            font-size: 11px;
        }

        main {
            margin-left: 250px;
            width: calc(100% - 250px);
            padding: 30px 38px;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 35px;
        }

        .welcome h1 {
            font-size: 27px;
            letter-spacing: -1px;
        }

        .welcome p {
            color: var(--muted);
            font-size: 13px;
            margin-top: 7px;
        }

        .top-actions {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .date {
            padding: 11px 15px;
            border: 1px solid var(--border);
            background: white;
            border-radius: 10px;
            font-size: 12px;
            color: #56615a;
        }

        .notification {
            width: 40px;
            height: 40px;
            background: white;
            border: 1px solid var(--border);
            border-radius: 11px;
            display: grid;
            place-items: center;
            position: relative;
        }

        .notification:after {
            content: '';
            width: 7px;
            height: 7px;
            background: var(--red);
            border-radius: 50%;
            position: absolute;
            top: 8px;
            right: 8px;
        }

        .section-title {
            font-size: 15px;
            font-weight: 700;
            margin-bottom: 17px;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 17px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 21px;
            border: 1px solid var(--border);
            border-radius: 15px;
        }

        .stat-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            background: #eaf5ef;
            color: var(--green);
            display: grid;
            place-items: center;
            border-radius: 11px;
        }

        .stat-card:nth-child(3) .stat-icon {
            background: #fff0ed;
            color: var(--red);
        }

        .stat-card:nth-child(4) .stat-icon {
            background: #f1effc;
            color: #7766c5;
        }

        .trend {
            font-size: 10px;
            color: var(--emerald);
            font-weight: 700;
        }

        .stat-card h2 {
            font-size: 29px;
            letter-spacing: -1px;
        }

        .stat-card p {
            color: var(--muted);
            font-size: 12px;
            margin-top: 5px;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 1.65fr 1fr;
            gap: 20px;
        }

        .panel {
            background: white;
            border: 1px solid var(--border);
            border-radius: 15px;
            padding: 23px;
            margin-bottom: 20px;
        }

        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 22px;
        }

        .panel-header h3 {
            font-size: 15px;
        }

        .view-all {
            color: var(--green);
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        th {
            color: var(--muted);
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: .7px;
            padding: 12px 8px;
            border-bottom: 1px solid var(--border);
        }

        td {
            padding: 16px 8px;
            font-size: 12px;
            border-bottom: 1px solid #f0f2ef;
        }

        .order-id {
            font-weight: 700;
            color: var(--green);
        }

        .status {
            padding: 6px 9px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 700;
            display: inline-block;
        }

        .preparing {
            background: #edf2ff;
            color: #536ec1;
        }

        .completed {
            background: #e8f6ed;
            color: #258553;
        }

        .pending {
            background: #fff3e3;
            color: #b47b25;
        }

        .alert-card {
            background: #fff8f7;
            border: 1px solid #f6dfdb;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 13px;
        }

        .alert-top {
            display: flex;
            align-items: center;
            gap: 9px;
            margin-bottom: 9px;
        }

        .alert-icon {
            color: var(--red);
        }

        .alert-card strong {
            font-size: 12px;
        }

        .alert-card p {
            font-size: 11px;
            color: #806d69;
            line-height: 1.7;
        }

        .alert-card small {
            display: block;
            color: var(--red);
            font-weight: 700;
            margin-top: 9px;
            font-size: 10px;
        }

        .safety-panel {
            background: var(--forest);
            color: white;
            border-radius: 15px;
            padding: 22px;
        }

        .safety-panel h3 {
            font-size: 15px;
            margin-bottom: 18px;
        }

        .safety-row {
            display: flex;
            justify-content: space-between;
            padding: 13px 0;
            border-bottom: 1px solid #2c453b;
            font-size: 12px;
        }

        .safety-row:last-child {
            border: 0;
        }

        .safety-value {
            color: #75d2a5;
            font-weight: 700;
        }

        .mobile-menu {
            display: none;
        }

        @media(max-width: 1100px) {
            .stats { grid-template-columns: repeat(2, 1fr); }
            .content-grid { grid-template-columns: 1fr; }
        }

        @media(max-width: 700px) {
            .sidebar { display: none; }
            main { margin: 0; width: 100%; padding: 22px 16px; }
            .topbar { align-items: flex-start; gap: 12px; }
            .welcome h1 { font-size: 21px; }
            .date { display: none; }
            .stats { gap: 10px; }
            .stat-card { padding: 15px; }
            .stat-card h2 { font-size: 23px; }
            .panel { overflow-x: auto; padding: 16px; }
            table { min-width: 480px; }
        }
    </style>
</head>

<body>
<div class="layout">

    <aside class="sidebar">
        <div class="brand">
            <div class="brand-icon">✦</div>
            <h2>Safe<span>Serve</span></h2>
        </div>

        <div class="nav-label">MAIN MENU</div>
        <a href="#" class="nav-link active"><i data-lucide="layout-dashboard"></i> Dashboard</a>
        <a href="#" class="nav-link"><i data-lucide="shopping-bag"></i> Orders</a>
        <a href="#" class="nav-link"><i data-lucide="utensils"></i> Menu Management</a>
        <a href="#" class="nav-link"><i data-lucide="chef-hat"></i> Kitchen Display</a>

        <div class="nav-label">SAFETY & OPERATIONS</div>
        <a href="#" class="nav-link"><i data-lucide="shield-check"></i> Allergy Management</a>
        <a href="#" class="nav-link"><i data-lucide="users"></i> Customers</a>
        <a href="#" class="nav-link"><i data-lucide="table"></i> Tables</a>

        <div class="nav-label">SYSTEM</div>
        <a href="#" class="nav-link"><i data-lucide="chart-no-axes-column"></i> Reports</a>
        <a href="#" class="nav-link"><i data-lucide="settings"></i> Settings</a>

        <div class="sidebar-bottom">
            <div class="user">
                <div class="avatar">A</div>
                <div>
                    <strong style="font-size:12px">Administrator</strong>
                    <small>Restaurant Manager</small>
                </div>
            </div>
        </div>
    </aside>

    <main>
        <header class="topbar">
            <div class="welcome">
                <h1>Good afternoon 👋</h1>
                <p>Here's what's happening in your restaurant today.</p>
            </div>

            <div class="top-actions">
                <div class="date" id="currentDate"></div>
                <div class="notification"><i data-lucide="bell"></i></div>
            </div>
        </header>

        <h3 class="section-title">Restaurant Overview</h3>

        <section class="stats">
            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon"><i data-lucide="shopping-bag"></i></div>
                    <span class="trend">Today's activity</span>
                </div>
                <h2>128</h2>
                <p>Total Orders</p>
            </div>

            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon"><i data-lucide="wallet"></i></div>
                    <span class="trend">Today's activity</span>
                </div>
                <h2>TZS 2.4M</h2>
                <p>Revenue</p>
            </div>

            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon"><i data-lucide="shield-alert"></i></div>
                    <span style="color:#dc5757;font-size:10px;font-weight:700">Needs attention</span>
                </div>
                <h2>03</h2>
                <p>Active Allergy Alerts</p>
            </div>

            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon"><i data-lucide="clock"></i></div>
                    <span class="trend">Live overview</span>
                </div>
                <h2>12</h2>
                <p>Pending Orders</p>
            </div>
        </section>

        <section class="content-grid">
            <div>
                <div class="panel">
                    <div class="panel-header">
                        <h3>Recent Orders</h3>
                        <a href="#" class="view-all">View all →</a>
                    </div>

                    <table>
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Table</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="order-id">#SS-1028</td>
                                <td>Table 05</td>
                                <td>TZS 35,000</td>
                                <td><span class="status preparing">Preparing</span></td>
                            </tr>
                            <tr>
                                <td class="order-id">#SS-1027</td>
                                <td>Table 12</td>
                                <td>TZS 24,000</td>
                                <td><span class="status completed">Completed</span></td>
                            </tr>
                            <tr>
                                <td class="order-id">#SS-1026</td>
                                <td>Table 03</td>
                                <td>TZS 18,500</td>
                                <td><span class="status pending">Pending</span></td>
                            </tr>
                            <tr>
                                <td class="order-id">#SS-1025</td>
                                <td>Table 08</td>
                                <td>TZS 42,000</td>
                                <td><span class="status preparing">Preparing</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="panel">
                    <div class="panel-header">
                        <h3>Order Activity</h3>
                        <span class="view-all">Today</span>
                    </div>
                    <p style="font-size:12px;color:#87918a;line-height:1.8">
                        Your restaurant activity overview will display order trends,
                        sales performance and kitchen processing times here.
                    </p>
                    <div style="display:flex;align-items:end;gap:12px;height:100px;margin-top:20px">
                        <div style="flex:1;height:40%;background:#dcece2;border-radius:6px 6px 0 0"></div>
                        <div style="flex:1;height:65%;background:#b6ddc6;border-radius:6px 6px 0 0"></div>
                        <div style="flex:1;height:50%;background:#dcece2;border-radius:6px 6px 0 0"></div>
                        <div style="flex:1;height:85%;background:#39a879;border-radius:6px 6px 0 0"></div>
                        <div style="flex:1;height:60%;background:#b6ddc6;border-radius:6px 6px 0 0"></div>
                        <div style="flex:1;height:95%;background:#174c3a;border-radius:6px 6px 0 0"></div>
                        <div style="flex:1;height:70%;background:#b6ddc6;border-radius:6px 6px 0 0"></div>
                    </div>
                    <div style="display:flex;justify-content:space-between;color:#87918a;font-size:10px;margin-top:10px">
                        <span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span><span>Sun</span>
                    </div>
                </div>
            </div>

            <div>
                <div class="panel">
                    <div class="panel-header">
                        <h3><i data-lucide="shield-alert" style="width:17px;vertical-align:middle;color:#dc5757"></i> Safety Alerts</h3>
                        <span style="font-size:10px;color:#dc5757;font-weight:700">3 ACTIVE</span>
                    </div>

                    <div class="alert-card">
                        <div class="alert-top">
                            <i data-lucide="alert-triangle" class="alert-icon"></i>
                            <strong>Peanut Allergy</strong>
                        </div>
                        <p>Table 05 · Order #SS-1028</p>
                        <small>STAFF REVIEW REQUIRED</small>
                    </div>

                    <div class="alert-card">
                        <div class="alert-top">
                            <i data-lucide="alert-triangle" class="alert-icon"></i>
                            <strong>Ingredient Information Missing</strong>
                        </div>
                        <p>Table 03 · Order #SS-1026</p>
                        <small>VERIFICATION REQUIRED</small>
                    </div>

                    <div class="alert-card">
                        <div class="alert-top">
                            <i data-lucide="alert-triangle" class="alert-icon"></i>
                            <strong>Milk Allergy</strong>
                        </div>
                        <p>Table 08 · Order #SS-1025</p>
                        <small>STAFF ACKNOWLEDGEMENT REQUIRED</small>
                    </div>
                </div>

                <div class="safety-panel">
                    <h3>Safety Performance</h3>
                    <div class="safety-row">
                        <span>Orders reviewed</span>
                        <span class="safety-value">116</span>
                    </div>
                    <div class="safety-row">
                        <span>Safety checks completed</span>
                        <span class="safety-value">98%</span>
                    </div>
                    <div class="safety-row">
                        <span>Awaiting staff review</span>
                        <span style="color:#f0a5a0;font-weight:700">03</span>
                    </div>
                </div>
            </div>
        </section>
    </main>
</div>

<script>
    lucide.createIcons();

    document.getElementById('currentDate').textContent =
        new Date().toLocaleDateString('en-US', {
            weekday: 'short',
            day: 'numeric',
            month: 'short',
            year: 'numeric'
        });
</script>
</body>
</html>
