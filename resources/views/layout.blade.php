<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LMS Portal</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons (Fixes missing icons crashing the UI) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <!-- Axios -->
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    
    <style>
        body { background-color: #f8f9fa; }
        .card { border: none; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        /* Ensure manual modals sit on top */
        .modal { z-index: 1050; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
        <div class="container">
            <a class="navbar-brand" href="#">LMS Portal</a>
            <button class="btn btn-light btn-sm" onclick="logout()" id="logoutBtn" style="display: none;">Logout</button>
        </div>
    </nav>

    <div class="container">
        <!-- Error Container for Global JS Errors -->
        <div id="global-error" class="alert alert-danger d-none"></div>
        @yield('content')
    </div>

    <!-- Manual JS Only (Bootstrap Bundle Removed) -->

    <script>
        // Setup API Config - Point explicitly to the Artisan Serve address
        axios.defaults.baseURL = 'http://127.0.0.1:8000/api';
        
        // Debugging: Catch syntax errors that freeze the page
        window.onerror = function(message, source, lineno, colno, error) {
            console.error("Global Error:", message, "Line:", lineno);
        };

        const token = localStorage.getItem('token');
        if (token) {
            axios.defaults.headers.common['Authorization'] = 'Bearer ' + token;
            const btn = document.getElementById('logoutBtn');
            if(btn) btn.style.display = 'block';
            
            // Start Auto-Refresh Logic
            startSessionRefresh();
        }

        // Global Error Handler
        axios.interceptors.response.use(
            response => response,
            error => {
                // Handle 401 (Unauthorized/Expired)
                if (error.response && error.response.status === 401) {
                    if (window.location.pathname !== '/' && window.location.pathname !== '/register') {
                        console.warn('Session expired. Logging out...');
                        logout();
                    }
                }
                // Handle Network Errors (Server Down/Wrong URL)
                if (!error.response) {
                    alert("Network Error: Cannot reach Backend. Is 'php artisan serve' running?");
                }
                return Promise.reject(error);
            }
        );

        function logout() {
            localStorage.clear();
            window.location.href = '/';
        }

        // --- GLOBAL MANUAL MODAL LOGIC ---
        // Defined here so it works on ALL pages (Course Manager, etc.)
        window.openModal = function(id) {
            const el = document.getElementById(id);
            if(el) {
                el.style.display = 'block';
                el.classList.add('show');
                el.style.backgroundColor = 'rgba(0,0,0,0.5)'; // Darken background
            } else {
                console.error("Modal not found:", id);
            }
        };

        window.closeModal = function(id) {
            const el = document.getElementById(id);
            if(el) {
                el.style.display = 'none';
                el.classList.remove('show');
            }
        };

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        };

        // --- AUTO REFRESH LOGIC ---
        function startSessionRefresh() {
            // Refresh token every 15 minutes to keep session alive
            setInterval(async () => {
                try {
                    const res = await axios.post('/refresh');
                    const newToken = res.data.access_token;
                    
                    localStorage.setItem('token', newToken);
                    axios.defaults.headers.common['Authorization'] = 'Bearer ' + newToken;
                    console.log('Session auto-refreshed');
                } catch (err) {
                    console.error('Failed to refresh session', err);
                }
            }, 15 * 60 * 1000); 
        }
    </script>
    @stack('scripts')
</body>
</html>