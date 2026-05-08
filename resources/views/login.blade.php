@extends('layout')

@section('content')
<div class="row justify-content-center mt-5">
    <div class="col-md-4">
        <div class="card p-4 shadow">
            <h3 class="text-center mb-4">LMS Login</h3>
            
            <!-- Error Alert Box -->
            <div id="error-msg" class="alert alert-danger d-none"></div>
            
            <!-- ADDED autocomplete="off" -->
            <form id="loginForm" autocomplete="off">
                <div class="mb-3">
                    <label class="form-label">Email Address</label>
                    <input type="email" id="email" class="form-control" placeholder="admin@example.com" required autocomplete="off">
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" id="password" class="form-control" placeholder="Password" required autocomplete="new-password">
                </div>
                <button type="submit" class="btn btn-primary w-100">Sign In</button>
            </form>

            <div class="text-center mt-3">
                <p class="small text-muted">System Setup: <a href="/register">Register Admin</a></p>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.getElementById('loginForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;
        const errorDiv = document.getElementById('error-msg');

        // Clear previous errors
        errorDiv.classList.add('d-none');
        errorDiv.innerText = '';

        try {
            // 1. Send Login Request to API
            const res = await axios.post('/login', { email, password });
            
            // 2. Save Token & User Info to Browser Memory
            localStorage.setItem('token', res.data.access_token);
            localStorage.setItem('role', res.data.user.role);
            localStorage.setItem('user_name', res.data.user.name);

            // 3. Configure Axios globally for future requests
            axios.defaults.headers.common['Authorization'] = 'Bearer ' + res.data.access_token;

            // 4. Redirect based on Role
            const role = res.data.user.role;
            if (role === 'admin') window.location.href = '/dashboard';
            else if (role === 'teacher') window.location.href = '/teacher-dashboard';
            else window.location.href = '/student-dashboard';

        } catch (err) {
            // Show error message
            errorDiv.classList.remove('d-none');
            errorDiv.innerText = err.response?.data?.message || 'Invalid Email or Password';
        }
    });
</script>
@endpush
@endsection