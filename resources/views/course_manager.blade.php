@extends('layout')

@section('content')
<div class="row">
    <!-- Header -->
    <div class="col-12 mb-4">
        <a href="/teacher-dashboard" class="text-decoration-none text-muted mb-2 d-inline-block">&larr; Back to Dashboard</a>
        <h2 id="courseTitle">Loading...</h2>
        <p class="text-muted" id="courseDesc"></p>
    </div>

    <!-- Tabs -->
    <div class="col-12 mb-4">
        <ul class="nav nav-tabs">
            <li class="nav-item">
                <button class="nav-link active" id="tab-content" onclick="switchTab('content')">Course Content</button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="tab-students" onclick="switchTab('students')">Students & Grades</button>
            </li>
        </ul>
    </div>

    <!-- CONTENT TAB -->
    <div class="col-12" id="view-content">
        
        <!-- Assignments Panel -->
        <div class="card mb-4 border-warning">
            <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Assignments</h5>
                <button onclick="openAssignmentModal()" class="btn btn-sm btn-dark fw-bold">+ New Assignment</button>
            </div>
            <div class="list-group list-group-flush" id="assignments-container">
                <!-- Assignments injected here -->
            </div>
        </div>

        <!-- Quizzes Panel -->
        <div class="card mb-4 border-primary">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Quizzes</h5>
                <button onclick="createQuiz()" class="btn btn-sm btn-light fw-bold">+ New Quiz</button>
            </div>
            <div class="list-group list-group-flush" id="quizzes-container">
                <!-- Quizzes injected here -->
            </div>
        </div>

        <!-- Sections & Materials -->
        <h4 class="mb-3 text-muted border-bottom pb-2">Study Materials</h4>
        
        <div class="card p-3 mb-4 bg-light">
            <!-- ADDED autocomplete="off" -->
            <form id="addSectionForm" class="d-flex gap-2" autocomplete="off">
                <input type="text" id="newSectionTitle" class="form-control" placeholder="New Section Title (e.g. Chapter 1)" required autocomplete="off">
                <button type="submit" class="btn btn-primary text-nowrap">+ Add Section</button>
            </form>
        </div>

        <div id="sections-container">
            <!-- Sections injected here -->
        </div>
    </div>

    <!-- STUDENTS TAB -->
    <div class="col-12 d-none" id="view-students">
        <div class="card shadow-sm">
            <div class="card-body">
                <table class="table table-hover">
                    <thead><tr><th>Student</th><th>Email</th><th>Enrolled</th><th>Performance</th></tr></thead>
                    <tbody id="students-table"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ==========================
     MANUAL MODALS
=========================== -->

<!-- 1. Add Material Modal (PDF/Video/Etc) -->
<div id="materialModal" class="modal" tabindex="-1" role="dialog" style="display: none; background: rgba(0,0,0,0.5);">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Upload Study Material</h5>
                <button type="button" class="btn-close" onclick="closeModal('materialModal')"></button>
            </div>
            <div class="modal-body">
                <!-- ADDED autocomplete="off" -->
                <form id="addMaterialForm" autocomplete="off">
                    <input type="hidden" id="activeSectionId">
                    <div class="mb-3"><input type="text" id="matTitle" class="form-control" placeholder="Title" required autocomplete="off"></div>
                    <div class="mb-3">
                        <label class="form-label small text-muted">Type</label>
                        <select id="matType" class="form-select">
                            <option value="pdf">PDF Document</option>
                            <option value="video">Video (MP4)</option>
                            <option value="document">Document (DOCX/DOC)</option>
                            <option value="image">Image (JPG/PNG)</option>
                            <option value="archive">Zip Archive</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-muted">File</label>
                        <input type="file" id="matFile" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Upload</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- 2. Add Assignment Modal -->
<div id="assignmentModal" class="modal" tabindex="-1" role="dialog" style="display: none; background: rgba(0,0,0,0.5);">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title text-dark">Create Assignment</h5>
                <button type="button" class="btn-close" onclick="closeModal('assignmentModal')"></button>
            </div>
            <div class="modal-body">
                <!-- ADDED autocomplete="off" -->
                <form id="addAssignmentForm" autocomplete="off">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Assignment Title</label>
                        <input type="text" id="assignTitle" class="form-control" required autocomplete="off">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Instructions</label>
                        <textarea id="assignContent" class="form-control" rows="3" placeholder="Write question here..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Question File (Optional)</label>
                        <input type="file" id="assignFile" class="form-control">
                        <small class="text-muted">Allowed: PDF, Docx, Image, Zip</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-danger">Deadline</label>
                        <input type="datetime-local" id="assignDate" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-warning w-100 fw-bold">Post Assignment</button>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    const courseId = Number("{{ $id }}");
    let courseData = null;

    document.addEventListener('DOMContentLoaded', () => {
        loadCourse();
    });

    // --- MANUAL MODAL LOGIC (No Bootstrap JS needed) ---
    function openModal(id) {
        document.getElementById(id).style.display = 'block';
        document.getElementById(id).classList.add('show');
    }
    window.closeModal = function(id) {
        document.getElementById(id).style.display = 'none';
        document.getElementById(id).classList.remove('show');
    }
    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
        }
    }

    // --- DATA LOADING ---
    async function loadCourse() {
        try {
            const [cRes, sRes] = await Promise.all([
                axios.get(`/courses/${courseId}`),
                axios.get(`/courses/${courseId}/students`)
            ]);
            courseData = cRes.data;
            renderHeader(courseData);
            renderContent(courseData);
            renderStudents(sRes.data);
        } catch (err) { 
            console.error(err);
            alert('Failed to load course data'); 
        }
    }

    function renderHeader(course) {
        document.getElementById('courseTitle').innerText = course.title;
        document.getElementById('courseDesc').innerText = course.description;
    }

    function renderContent(course) {
        const secContainer = document.getElementById('sections-container');
        const quizContainer = document.getElementById('quizzes-container');
        const assignContainer = document.getElementById('assignments-container');
        
        secContainer.innerHTML = '';
        quizContainer.innerHTML = '';
        assignContainer.innerHTML = '';

        // 1. Quizzes
        if (course.quizzes.length === 0) quizContainer.innerHTML = `<div class="list-group-item text-muted">No quizzes yet.</div>`;
        course.quizzes.forEach(q => {
            const badge = q.is_published ? '<span class="badge bg-success">Published</span>' : '<span class="badge bg-secondary">Draft</span>';
            quizContainer.innerHTML += `
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div><strong>${q.title}</strong> ${badge}</div>
                    <div>
                        <a href="/quiz-builder/${q.id}" class="btn btn-sm btn-link">Edit Questions</a>
                        <button onclick="deleteQuiz(${q.id})" class="btn btn-sm text-danger">&times;</button>
                    </div>
                </div>
            `;
        });

        // 2. Assignments & Materials
        let hasAssignments = false;

        course.sections.forEach(sec => {
            if (sec.title === 'General Resources') {
                sec.materials.forEach(mat => {
                    if (mat.type === 'assignment') {
                        hasAssignments = true;
                        renderAssignmentItem(assignContainer, mat, sec.title);
                    }
                });
                return;
            }

            let matsHtml = '';
            
            sec.materials.forEach(mat => {
                if (mat.type === 'assignment') {
                    hasAssignments = true;
                    renderAssignmentItem(assignContainer, mat, sec.title);
                } else {
                    let icon = '📄';
                    if (mat.type === 'video') icon = '📺';
                    else if (mat.type === 'image') icon = '🖼️';
                    else if (mat.type === 'archive') icon = '📦';
                    else if (mat.type === 'document') icon = '📃';

                    matsHtml += `
                        <div class="d-flex justify-content-between align-items-center p-2 border-bottom">
                            <div>
                                <span class="me-2 fs-5">${icon}</span> 
                                <a href="${mat.file_url || '#'}" target="_blank">${mat.title}</a>
                            </div>
                            <button onclick="deleteMaterial(${mat.id})" class="btn btn-sm text-danger">&times;</button>
                        </div>
                    `;
                }
            });

            secContainer.innerHTML += `
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center bg-light">
                        <h5 class="mb-0 text-dark">${sec.title}</h5>
                        <div class="btn-group">
                            <button onclick="openMaterialModal(${sec.id})" class="btn btn-sm btn-outline-primary">+ Add File</button>
                            <button onclick="deleteSection(${sec.id})" class="btn btn-sm btn-outline-danger ms-2">Delete</button>
                        </div>
                    </div>
                    <div class="card-body p-0">${matsHtml || '<div class="p-3 text-muted">No study materials.</div>'}</div>
                </div>
            `;
        });

        if (!hasAssignments) assignContainer.innerHTML = `<div class="list-group-item text-muted">No assignments yet.</div>`;
    }

    function renderAssignmentItem(container, mat, sectionTitle) {
        container.innerHTML += `
            <div class="list-group-item d-flex justify-content-between align-items-center">
                <div>
                    <strong>${mat.title}</strong> 
                    ${sectionTitle !== 'General Resources' ? `<span class="badge bg-light text-dark border ms-1">${sectionTitle}</span>` : ''}
                    <br><small class="text-danger">Due: ${new Date(mat.due_date).toLocaleString()}</small>
                </div>
                <div>
                    <a href="/assignment-grader/${mat.id}" class="btn btn-sm btn-outline-dark">Grade</a>
                    <button onclick="deleteMaterial(${mat.id})" class="btn btn-sm text-danger ms-1">&times;</button>
                </div>
            </div>
        `;
    }

    function renderStudents(students) {
        const tbody = document.getElementById('students-table');
        tbody.innerHTML = '';
        if (students.length === 0) { tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">No students enrolled.</td></tr>'; return; }
        
        students.forEach(s => {
            const quizGrades = s.quiz_results.map(q => `<span class="badge bg-success">Q${q.quiz_id}: ${q.score}</span>`).join(' ');
            const assignGrades = s.assignment_results ? s.assignment_results.map(a => `<span class="badge bg-warning text-dark">Assign: ${a.grade ?? 'Pending'}</span>`).join(' ') : '';

            tbody.innerHTML += `
                <tr>
                    <td>${s.name}</td>
                    <td>${s.email}</td>
                    <td>${new Date(s.enrolled_at).toLocaleDateString()}</td>
                    <td>
                        <div>${quizGrades || '<small class="text-muted">No quizzes</small>'}</div>
                        <div class="mt-1">${assignGrades}</div>
                    </td>
                </tr>
            `;
        });
    }

    // --- ACTIONS ---
    window.switchTab = function(tab) {
        document.querySelectorAll('.nav-link').forEach(b => b.classList.remove('active'));
        document.getElementById('tab-' + tab).classList.add('active');
        if (tab === 'content') { 
            document.getElementById('view-content').classList.remove('d-none'); 
            document.getElementById('view-students').classList.add('d-none'); 
        } else { 
            document.getElementById('view-content').classList.add('d-none'); 
            document.getElementById('view-students').classList.remove('d-none'); 
        }
    }

    document.getElementById('addSectionForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        try { 
            await axios.post(`/courses/${courseId}/sections`, { title: document.getElementById('newSectionTitle').value, order: courseData.sections.length + 1 }); 
            document.getElementById('newSectionTitle').value = ''; 
            loadCourse(); 
        } catch (err) { alert(err.response?.data?.message || 'Failed to add section'); }
    });

    window.deleteSection = function(id) { if(confirm('Delete section?')) axios.delete(`/sections/${id}`).then(loadCourse); }

    window.createQuiz = async function() {
        const title = prompt("Enter Quiz Title:"); if (!title) return;
        const timeLimit = prompt("Enter Time Limit in Minutes (0 for no limit):", "10");
        if (timeLimit === null) return;

        try { 
            const res = await axios.post('/quizzes', { 
                course_id: courseId, 
                title: title, 
                passing_score: 50 
            }); 
            
            await axios.put(`/quizzes/${res.data.id}`, { time_limit: parseInt(timeLimit) || 0 });

            loadCourse(); 
        } catch (err) { alert('Failed to create quiz'); }
    }

    window.deleteQuiz = function(id) { if(confirm('Delete quiz?')) axios.delete(`/quizzes/${id}`).then(loadCourse); }

    // --- MODAL HELPERS ---
    window.openMaterialModal = function(secId) { 
        document.getElementById('activeSectionId').value = secId; 
        openModal('materialModal');
    }

    window.openAssignmentModal = function() {
        // No dropdown needed anymore
        openModal('assignmentModal');
    }

    // --- FORM SUBMISSIONS ---

    // 1. Add Material (Standard)
    document.getElementById('addMaterialForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const secId = document.getElementById('activeSectionId').value;
        const formData = new FormData();
        formData.append('title', document.getElementById('matTitle').value);
        formData.append('type', document.getElementById('matType').value);
        formData.append('file', document.getElementById('matFile').files[0]);
        // FIX: Ensure 'content' key exists to prevent backend error
        formData.append('content', ''); 

        try { 
            await axios.post(`/sections/${secId}/materials`, formData, { headers: { "Content-Type": "multipart/form-data" } }); 
            closeModal('materialModal');
            document.getElementById('addMaterialForm').reset(); 
            loadCourse(); 
        } catch (err) { 
            alert(err.response?.data?.message || 'Upload failed. Check file size/type.'); 
        }
    });

    // 2. Add Assignment (Specific)
    document.getElementById('addAssignmentForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        
        let secId = null;
        
        try {
            // Logic to Auto-Create "General Resources" Section
            // Find existing
            const generalSec = courseData.sections.find(s => s.title === 'General Resources');
            
            if (generalSec) {
                secId = generalSec.id;
            } else {
                // Create if missing
                const secRes = await axios.post(`/courses/${courseId}/sections`, { 
                    title: "General Resources", 
                    order: 0 
                });
                secId = secRes.data.id;
            }

            const formData = new FormData();
            formData.append('type', 'assignment');
            formData.append('title', document.getElementById('assignTitle').value);
            formData.append('due_date', document.getElementById('assignDate').value);
            
            const content = document.getElementById('assignContent').value;
            formData.append('content', content || ''); 
            
            const file = document.getElementById('assignFile').files[0];
            if(file) formData.append('file', file);

            await axios.post(`/sections/${secId}/materials`, formData, { headers: { "Content-Type": "multipart/form-data" } }); 
            closeModal('assignmentModal');
            document.getElementById('addAssignmentForm').reset(); 
            loadCourse(); 
        } catch (err) { 
            alert(err.response?.data?.message || 'Failed to post assignment'); 
        }
    });

    window.deleteMaterial = function(id) { if(confirm('Delete file?')) axios.delete(`/materials/${id}`).then(loadCourse); }
</script>
@endpush
@endsection