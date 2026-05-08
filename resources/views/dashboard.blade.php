@extends('layout')

@section('content')
<div class="row">
    <!-- Header & Stats -->
    <div class="col-12 mb-4">
        <h2 class="mb-4">Admin Dashboard <small class="text-muted fs-6">Welcome, <span id="adminName"></span></small></h2>
        <div class="row g-3" id="stats-container">
            <div class="col-md-3"><div class="card p-3 bg-primary text-white"><h3>Loading...</h3><small>Students</small></div></div>
        </div>
    </div>

    <!-- TABS -->
    <div class="col-12 mb-4">
        <ul class="nav nav-pills">
            <li class="nav-item">
                <button class="nav-link active" id="btn-users" onclick="showTab('users')">Manage Users</button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="btn-courses" onclick="showTab('courses')">Manage Courses</button>
            </li>
        </ul>
    </div>

    <!-- TAB 1: USER MANAGEMENT -->
    <div id="tab-users" class="row">
        <div class="col-md-4">
            <div class="card p-4 mb-4">
                <h5 class="card-title">Create New User</h5>
                <!-- ADDED autocomplete="off" -->
                <form id="createUserForm" autocomplete="off">
                    <div class="mb-2"><input type="text" id="newName" class="form-control" placeholder="Full Name" required autocomplete="off"></div>
                    <div class="mb-2"><input type="email" id="newEmail" class="form-control" placeholder="Email" required autocomplete="off"></div>
                    <div class="mb-2"><input type="text" id="newPassword" class="form-control" placeholder="Password (min 6)" required autocomplete="new-password"></div>
                    <div class="mb-3">
                        <select id="newRole" class="form-select">
                            <option value="student">Student</option>
                            <option value="teacher">Teacher</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-success w-100">Create Account</button>
                </form>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card p-4">
                <h5 class="card-title mb-3">User Directory</h5>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead><tr><th>Name</th><th>Role</th><th>Status</th><th>Action</th></tr></thead>
                        <tbody id="userTableBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 2: COURSE MANAGEMENT -->
    <div id="tab-courses" class="row d-none">
        <div class="col-md-4">
            <div class="card p-4 mb-4">
                <h5 class="card-title">Create New Course</h5>
                <!-- ADDED autocomplete="off" -->
                <form id="createCourseForm" autocomplete="off">
                    <div class="mb-2"><input type="text" id="courseTitle" class="form-control" placeholder="Course Title" required autocomplete="off"></div>
                    <div class="mb-2"><textarea id="courseDesc" class="form-control" placeholder="Description" rows="3" required></textarea></div>
                    <div class="mb-3">
                        <label class="form-label small text-muted">Assign Teacher</label>
                        <select id="courseTeacher" class="form-select" required>
                            <option value="">Select Teacher...</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Create Course</button>
                </form>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card p-4">
                <h5 class="card-title mb-3">Active Courses</h5>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead><tr><th>Title</th><th>Teacher</th><th>Action</th></tr></thead>
                        <tbody id="courseTableBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const role = localStorage.getItem('role');
        if (role !== 'admin') { window.location.href = '/'; return; }
        
        document.getElementById('adminName').innerText = localStorage.getItem('user_name');
        
        loadStats();
        loadUsers(); 
        loadCourses();
    });

    function showTab(tab) {
        document.getElementById('tab-users').classList.add('d-none');
        document.getElementById('tab-courses').classList.add('d-none');
        document.getElementById('btn-users').classList.remove('active');
        document.getElementById('btn-courses').classList.remove('active');

        document.getElementById('tab-' + tab).classList.remove('d-none');
        document.getElementById('btn-' + tab).classList.add('active');
    }

    async function loadStats() {
        try {
            const res = await axios.get('/admin/stats');
            const s = res.data;
            document.getElementById('stats-container').innerHTML = `
                <div class="col-md-3"><div class="card p-3 bg-primary text-white"><h3>${s.total_students}</h3><small>Students</small></div></div>
                <div class="col-md-3"><div class="card p-3 bg-success text-white"><h3>${s.total_teachers}</h3><small>Teachers</small></div></div>
                <div class="col-md-3"><div class="card p-3 bg-warning text-dark"><h3>${s.total_courses}</h3><small>Courses</small></div></div>
                <div class="col-md-3"><div class="card p-3 bg-info text-white"><h3>${s.total_quizzes}</h3><small>Quizzes</small></div></div>
            `;
        } catch (e) {}
    }

    async function loadUsers() {
        try {
            const res = await axios.get('/admin/users');
            const users = res.data;
            
            const tbody = document.getElementById('userTableBody');
            tbody.innerHTML = '';
            const teacherSelect = document.getElementById('courseTeacher');
            teacherSelect.innerHTML = '<option value="">Select Teacher...</option>';

            users.forEach(user => {
                const statusBadge = user.is_active ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Banned</span>';
                tbody.innerHTML += `
                    <tr>
                        <td>${user.name}<br><small class="text-muted">${user.email}</small></td>
                        <td><span class="badge bg-secondary text-uppercase">${user.role}</span></td>
                        <td>${statusBadge}</td>
                        <td>
                            <button onclick="toggleStatus(${user.id}, ${user.is_active})" class="btn btn-sm btn-outline-secondary">Toggle</button>
                            <button onclick="deleteUser(${user.id})" class="btn btn-sm btn-danger ms-1">&times;</button>
                        </td>
                    </tr>
                `;

                if (user.role === 'teacher') {
                    const option = document.createElement('option');
                    option.value = user.id;
                    option.innerText = user.name + ' (' + user.email + ')';
                    teacherSelect.appendChild(option);
                }
            });
        } catch (e) { alert('Failed to load users'); }
    }

    async function loadCourses() {
        try {
            const res = await axios.get('/courses');
            const tbody = document.getElementById('courseTableBody');
            tbody.innerHTML = '';
            
            res.data.forEach(c => {
                tbody.innerHTML += `
                    <tr>
                        <td>${c.title}</td>
                        <td>${c.teacher ? c.teacher.name : '<span class="text-danger">Unassigned</span>'}</td>
                        <td><button onclick="deleteCourse(${c.id})" class="btn btn-sm btn-danger">Delete</button></td>
                    </tr>
                `;
            });
        } catch (e) {}
    }

    document.getElementById('createUserForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        try {
            await axios.post('/admin/users', {
                name: document.getElementById('newName').value,
                email: document.getElementById('newEmail').value,
                password: document.getElementById('newPassword').value,
                role: document.getElementById('newRole').value
            });
            alert('User Created!');
            document.getElementById('createUserForm').reset();
            loadUsers(); loadStats();
        } catch (err) { alert(err.response?.data?.message || 'Failed'); }
    });

    document.getElementById('createCourseForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        try {
            await axios.post('/courses', {
                title: document.getElementById('courseTitle').value,
                description: document.getElementById('courseDesc').value,
                teacher_id: document.getElementById('courseTeacher').value
            });
            alert('Course Created!');
            document.getElementById('createCourseForm').reset();
            loadCourses(); loadStats();
        } catch (err) { alert(err.response?.data?.message || 'Failed'); }
    });

    async function deleteUser(id) { if(confirm('Delete user?')) await axios.delete(`/admin/users/${id}`); loadUsers(); }
    async function deleteCourse(id) { if(confirm('Delete course?')) await axios.delete(`/courses/${id}`); loadCourses(); }
    
    async function toggleStatus(id, status) {
        await axios.put(`/admin/users/${id}`, { is_active: !status });
        loadUsers();
    }
</script>
@endpush
@endsection