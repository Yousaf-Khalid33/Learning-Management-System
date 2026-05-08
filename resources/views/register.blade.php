@extends('layout')

@section('content')
<div class="row justify-content-center mt-5">
    <div class="col-md-4">
        <div class="card p-4 shadow">
            <h3 class="text-center mb-4">System Setup</h3>
            <div class="alert alert-info small text-center">
                This form is for creating the <strong>Initial System Administrator</strong> only.
            </div>
            
            <div id="error-msg" class="alert alert-danger d-none"></div>

            <form id="registerForm">
                <div class="mb-3">
                    <label>Full Name</label>
                    <input type="text" id="name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Email</label>
                    <input type="email" id="email" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Password</label>
                    <input type="password" id="password" class="form-control" placeholder="Min 6 characters" required>
                </div>
                
                <!-- Force Admin Role -->
                <input type="hidden" id="role" value="admin">
                
                <button type="submit" class="btn btn-success w-100">Create Admin Account</button>
            </form>
            <div class="text-center mt-3">
                <a href="/">Back to Login</a>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.getElementById('registerForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const data = {
            name: document.getElementById('name').value,
            email: document.getElementById('email').value,
            password: document.getElementById('password').value,
            role: 'admin'
        };

        const errorDiv = document.getElementById('error-msg');
        errorDiv.classList.add('d-none');

        try {
            // 1. Send Register Request
            const res = await axios.post('/register', data);
            
            // 2. Auto-Login (Save Token)
            localStorage.setItem('token', res.data.access_token);
            localStorage.setItem('role', res.data.user.role);
            localStorage.setItem('user_name', res.data.user.name);
            
            // 3. Set Header for future requests
            axios.defaults.headers.common['Authorization'] = 'Bearer ' + res.data.access_token;

            alert('System Admin Created Successfully!');
            window.location.href = '/dashboard';

        } catch (err) {
            errorDiv.classList.remove('d-none');
            errorDiv.innerText = err.response?.data?.message || 'Registration failed';
        }
    });
</script>
@endpush
@endsection