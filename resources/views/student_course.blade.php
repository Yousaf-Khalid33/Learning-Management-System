@extends('layout')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-10">
        
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-start mb-4">
            <div>
                <a href="/student-dashboard" class="text-decoration-none text-muted mb-2 d-inline-block">&larr; Back to Dashboard</a>
                <h2 id="courseTitle">Loading...</h2>
                <p class="text-muted" id="courseDesc"></p>
                <div class="d-flex align-items-center mt-2">
                    <span class="badge bg-secondary me-2">Instructor</span>
                    <span id="teacherName" class="fw-bold text-dark"></span>
                </div>
            </div>
            <button onclick="claimCertificate()" class="btn btn-outline-success">
                <i class="bi bi-award"></i> Claim Certificate
            </button>
        </div>

        <!-- 1. QUIZZES SECTION -->
        <div class="card mb-4 border-info">
            <div class="card-header bg-info text-white fw-bold">Quizzes</div>
            <div class="list-group list-group-flush" id="quizzes-container">
                <!-- Quizzes injected here -->
            </div>
        </div>

        <!-- 2. COURSE CONTENT -->
        <h4 class="mb-3 text-muted border-bottom pb-2">Course Content</h4>
        <div id="content-container">
            <!-- Sections injected here -->
        </div>

    </div>
</div>

@push('scripts')
<script>
    const courseId = Number("{{ $id }}");

    document.addEventListener('DOMContentLoaded', () => {
        loadCourse();
    });

    async function loadCourse() {
        try {
            const res = await axios.get(`/courses/${courseId}`);
            const course = res.data;
            
            document.getElementById('courseTitle').innerText = course.title;
            document.getElementById('courseDesc').innerText = course.description;
            document.getElementById('teacherName').innerText = course.teacher.name;

            renderQuizzes(course.quizzes);
            renderSections(course.sections);
        } catch (err) {
            if(err.response && err.response.status === 403) {
                alert(err.response.data.message);
                window.location.href = '/student-dashboard';
            } else {
                alert('Failed to load course');
            }
        }
    }

    function renderQuizzes(quizzes) {
        const container = document.getElementById('quizzes-container');
        if (!quizzes || quizzes.length === 0) {
            container.innerHTML = '<div class="list-group-item text-muted">No quizzes available.</div>';
            return;
        }

        let html = '';
        quizzes.forEach(q => {
            // Determine Attempt Status
            let statusBadge = '<span class="badge bg-secondary">Not Attempted</span>';
            let scoreText = '';
            let btnText = 'Start Quiz';
            let btnClass = 'btn-primary';

            if (q.submissions && q.submissions.length > 0) {
                // User has taken this quiz
                const score = q.submissions[0].score; // Gets latest score
                statusBadge = '<span class="badge bg-success">Completed</span>';
                scoreText = `<div class="mt-1 fw-bold text-primary">Score: ${score}%</div>`;
                btnText = 'Retake Not Allowed';
                btnClass = 'btn-secondary disabled';
            }

            if (q.is_published) {
                html += `
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0 fw-bold">${q.title}</h6>
                            <small class="text-muted">${q.total_questions} Questions</small>
                            <div class="mt-1">${statusBadge}</div>
                            ${scoreText}
                        </div>
                        <a href="${btnClass.includes('disabled') ? '#' : '/take-quiz/'+q.id}" 
                           class="btn btn-sm ${btnClass}" 
                           ${btnClass.includes('disabled') ? 'disabled' : ''}>
                           ${btnText}
                        </a>
                    </div>
                `;
            } else {
                html += `
                    <div class="list-group-item d-flex justify-content-between align-items-center bg-light text-muted">
                        <div><h6 class="mb-0">${q.title}</h6><small>Hidden by Instructor</small></div>
                        <button class="btn btn-sm btn-secondary" disabled>Locked</button>
                    </div>
                `;
            }
        });
        container.innerHTML = html;
    }

    function renderSections(sections) {
        const container = document.getElementById('content-container');
        if (!sections || sections.length === 0) {
            container.innerHTML = '<p class="text-muted">No content uploaded yet.</p>';
            return;
        }

        container.innerHTML = ''; 

        sections.forEach(sec => {
            const sectionCard = document.createElement('div');
            sectionCard.className = 'card mb-4 shadow-sm border-0';
            
            const header = document.createElement('div');
            header.className = 'card-header bg-light fw-bold';
            header.innerText = sec.title === 'General Resources' ? 'Assignments and Tasks' : sec.title;
            sectionCard.appendChild(header);

            const body = document.createElement('div');
            body.className = 'card-body p-0';

            if (sec.materials.length === 0) {
                body.innerHTML = '<div class="p-3 text-muted small">Empty section</div>';
            } else {
                sec.materials.forEach(m => {
                    const matDiv = document.createElement('div');
                    matDiv.className = 'p-3 border-bottom bg-white';
                    
                    let icon = '📄';
                    if (m.type === 'video') icon = '📺';
                    if (m.type === 'assignment') icon = '📝';

                    let contentHtml = `
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <span class="fs-5 me-2">${icon}</span>
                                <strong class="text-dark">${m.title}</strong>
                                ${m.type === 'assignment' ? '<span class="badge bg-warning text-dark ms-2">Assignment</span>' : ''}
                                ${m.due_date ? `<br><small class="text-danger ms-4">Due: ${new Date(m.due_date).toLocaleString()}</small>` : ''}
                            </div>
                    `;

                    if (m.file_url) {
                        contentHtml += `<a href="${m.file_url}" target="_blank" class="btn btn-sm btn-outline-primary">Download/View</a>`;
                    }
                    
                    contentHtml += `</div>`; 

                    if (m.content) {
                        contentHtml += `<div class="alert alert-secondary small mt-2 ms-4">${m.content}</div>`;
                    }

                    if (m.type === 'assignment') {
                        contentHtml += `<div id="assign-status-${m.id}" class="ms-4 mt-3 p-3 bg-light rounded border">Loading status...</div>`;
                    }

                    matDiv.innerHTML = contentHtml;
                    body.appendChild(matDiv);

                    if (m.type === 'assignment') {
                        setTimeout(() => loadAssignmentStatus(m.id), 0);
                    }
                });
            }

            sectionCard.appendChild(body);
            container.appendChild(sectionCard);
        });
    }

    async function loadAssignmentStatus(materialId) {
        const container = document.getElementById(`assign-status-${materialId}`);
        try {
            const res = await axios.get(`/materials/${materialId}/my-submission`);
            if (res.data.submitted) {
                const sub = res.data.submission;
                const statusBadge = sub.is_late 
                    ? '<span class="badge bg-danger">Submitted Late</span>' 
                    : '<span class="badge bg-success">Submitted On Time</span>';
                
                container.innerHTML = `
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong>Status:</strong> ${statusBadge}<br>
                            <small class="text-muted">Date: ${new Date(sub.created_at).toLocaleString()}</small>
                            ${sub.grade ? `<br><strong>Grade: ${sub.grade}/100</strong>` : ''}
                        </div>
                        <button onclick="alert('You have already submitted.')" class="btn btn-sm btn-secondary" disabled>Submitted</button>
                    </div>
                `;
            } else {
                container.innerHTML = `
                    <label class="form-label small fw-bold">Upload Your Work:</label>
                    <div class="input-group input-group-sm">
                        <input type="file" class="form-control" id="file-${materialId}">
                        <button class="btn btn-success" onclick="submitAssignment(${materialId})">Submit</button>
                    </div>
                `;
            }
        } catch (err) {
            container.innerText = "Error loading status.";
        }
    }

    async function submitAssignment(materialId) {
        const fileInput = document.getElementById(`file-${materialId}`);
        const file = fileInput.files[0];

        if (!file) return alert("Please select a file first.");

        const formData = new FormData();
        formData.append('file', file);

        try {
            const btn = fileInput.nextElementSibling;
            btn.innerText = "Uploading...";
            btn.disabled = true;

            await axios.post(`/materials/${materialId}/submit`, formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });
            
            alert("Assignment Submitted Successfully!");
            loadAssignmentStatus(materialId); 
        } catch (err) {
            alert(err.response?.data?.message || "Submission failed");
            const btn = fileInput.nextElementSibling;
            btn.innerText = "Submit";
            btn.disabled = false;
        }
    }

    async function claimCertificate() {
        try {
            const res = await axios.post(`/courses/${courseId}/certificate`);
            window.open(res.data.certificate.file_url, '_blank');
        } catch (err) {
            alert(err.response?.data?.message || 'Cannot claim certificate yet.');
        }
    }
</script>
@endpush
@endsection