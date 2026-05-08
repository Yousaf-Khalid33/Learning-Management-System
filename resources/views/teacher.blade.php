@extends('layout')

@section('content')
<div class="row">
    <div class="col-12 mb-4 d-flex justify-content-between align-items-center">
        <h2>Teacher Dashboard <small class="text-muted fs-6">Welcome, <span id="teacherName"></span></small></h2>
        <button class="btn btn-primary" onclick="alert('Contact Admin to create new courses')">Request Course</button>
    </div>

    <div class="col-12">
        <div id="loading" class="text-center p-5">Loading your courses...</div>
        <div class="row g-4" id="course-container">
            <!-- Courses will be injected here -->
        </div>
        <div id="no-courses" class="alert alert-info text-center d-none">
            You don't have any assigned courses yet.
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const name = localStorage.getItem('user_name');
        const role = localStorage.getItem('role');

        if (role !== 'teacher') {
            window.location.href = '/';
            return;
        }

        document.getElementById('teacherName').innerText = name;
        loadCourses();
    });

    async function loadCourses() {
        try {
            const res = await axios.get('/teacher/courses');
            const courses = res.data;
            const container = document.getElementById('course-container');
            
            document.getElementById('loading').classList.add('d-none');

            if (courses.length === 0) {
                document.getElementById('no-courses').classList.remove('d-none');
                return;
            }

            courses.forEach(course => {
                const html = `
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="badge bg-success">Active</span>
                                </div>
                                <h5 class="card-title">${course.title}</h5>
                                <p class="card-text text-muted small">${course.description}</p>
                                <hr>
                                <div class="d-flex justify-content-between text-muted small mb-3">
                                    <span>${course.sections_count || 0} Sections</span>
                                    <span>${course.enrollments_count || 0} Students</span>
                                </div>
                                <button onclick="manageCourse(${course.id})" class="btn btn-outline-primary w-100">Manage Course</button>
                            </div>
                        </div>
                    </div>
                `;
                container.innerHTML += html;
            });

        } catch (err) {
            console.error(err);
            alert('Failed to load courses.');
        }
    }

    function manageCourse(id) {
        // We will build the Course Manager view next
        window.location.href = `/course-manager/${id}`;
    }
</script>
@endpush
@endsection