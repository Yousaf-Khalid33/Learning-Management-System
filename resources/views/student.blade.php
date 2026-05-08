@extends('layout')

@section('content')
<div class="row">
    <div class="col-12 mb-4">
        <h2>Student Dashboard <small class="text-muted fs-6">Welcome, <span id="studentName"></span></small></h2>
    </div>

    <!-- MY ENROLLMENTS -->
    <div class="col-12 mb-5">
        <h4 class="text-primary border-bottom pb-2 mb-3">My Enrollments</h4>
        <div class="row g-4" id="my-enrollments-container">
            <!-- Enrolled courses go here -->
        </div>
        <div id="no-enrollments" class="alert alert-warning d-none">You haven't joined any courses yet.</div>
    </div>

    <!-- AVAILABLE COURSES -->
    <div class="col-12">
        <h4 class="text-success border-bottom pb-2 mb-3">Available Courses</h4>
        <div id="loading" class="text-center p-3">Loading courses...</div>
        <div class="row g-4" id="available-courses-container">
            <!-- Available courses go here -->
        </div>
    </div>
</div>

@push('scripts')
<script>
    let myCourseIds = [];

    document.addEventListener('DOMContentLoaded', () => {
        const name = localStorage.getItem('user_name');
        const role = localStorage.getItem('role');

        if (role !== 'student') {
            window.location.href = '/';
            return;
        }

        document.getElementById('studentName').innerText = name;
        loadData();
    });

    async function loadData() {
        try {
            // Fetch My Enrollments & All Courses
            const [myRes, allRes] = await Promise.all([
                axios.get('/my-courses'),
                axios.get('/courses')
            ]);

            const myEnrollments = myRes.data;
            const allCourses = allRes.data;

            // 1. Render My Enrollments
            const myContainer = document.getElementById('my-enrollments-container');
            if (myEnrollments.length === 0) {
                document.getElementById('no-enrollments').classList.remove('d-none');
            } else {
                myEnrollments.forEach(item => {
                    myCourseIds.push(item.course.id);
                    const html = `
                        <div class="col-md-4">
                            <div class="card h-100 border-primary">
                                <div class="card-body">
                                    <h5 class="card-title">${item.course.title}</h5>
                                    <p class="card-text text-muted small">Teacher: ${item.course.teacher.name}</p>
                                    <a href="/student-course/${item.course.id}" class="btn btn-primary w-100">Go to Class</a>
                                </div>
                            </div>
                        </div>
                    `;
                    myContainer.innerHTML += html;
                });
            }

            // 2. Render Available Courses
            const availContainer = document.getElementById('available-courses-container');
            document.getElementById('loading').classList.add('d-none');

            allCourses.forEach(course => {
                const isEnrolled = myCourseIds.includes(course.id);
                
                const actionBtn = isEnrolled 
                    ? `<button class="btn btn-secondary w-100" disabled>Already Enrolled</button>`
                    : `<button onclick="enroll(${course.id})" class="btn btn-success w-100">Enroll Now</button>`;

                const html = `
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title">${course.title}</h5>
                                <p class="card-text small">${course.description}</p>
                                <p class="text-muted small mb-3">Teacher: ${course.teacher.name}</p>
                                ${actionBtn}
                            </div>
                        </div>
                    </div>
                `;
                availContainer.innerHTML += html;
            });

        } catch (err) {
            console.error(err);
            alert('Failed to load data.');
        }
    }

    async function enroll(courseId) {
        try {
            await axios.post(`/courses/${courseId}/enroll`);
            alert('Enrolled Successfully!');
            window.location.reload(); // Refresh to update lists
        } catch (err) {
            alert(err.response?.data?.message || 'Enrollment failed');
        }
    }
</script>
@endpush
@endsection