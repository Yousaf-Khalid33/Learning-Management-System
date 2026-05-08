@extends('layout')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-10">
        
        <div class="mb-4">
            <a href="#" onclick="history.back()" class="text-decoration-none text-muted">&larr; Back to Course</a>
            <h2 class="mt-2">Grading: <span id="assignmentTitle">Loading...</span></h2>
        </div>

        <div class="card shadow-sm border-warning">
            <div class="card-header bg-warning text-dark fw-bold">Student Submissions</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Student</th>
                                <th>Submitted File</th>
                                <th>Submission Date</th>
                                <th>Status</th>
                                <th>Grade (0-100)</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="submissionsTable">
                            <tr><td colspan="6" class="text-center p-4">Loading submissions...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

@push('scripts')
<script>
    // FIX: Using Number() ensures valid JS syntax even before Blade renders
    const materialId = Number("{{ $id }}");

    document.addEventListener('DOMContentLoaded', () => {
        loadSubmissions();
    });

    async function loadSubmissions() {
        try {
            // We fetch the submissions for this specific material
            const res = await axios.get(`/materials/${materialId}/submissions`);
            const submissions = res.data;
            renderTable(submissions);
            
            // Set a generic title or update if data allows
            if(submissions.length > 0) {
               document.getElementById('assignmentTitle').innerText = "Assignment Submissions";
            } else {
               document.getElementById('assignmentTitle').innerText = "Assignment Details";
            }

        } catch (err) {
            alert('Failed to load submissions');
        }
    }

    function renderTable(data) {
        const tbody = document.getElementById('submissionsTable');
        
        if (data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center p-5 text-muted">No students have submitted this assignment yet.</td></tr>';
            return;
        }

        tbody.innerHTML = '';
        data.forEach(sub => {
            const date = new Date(sub.created_at).toLocaleString();
            const lateBadge = sub.is_late 
                ? '<span class="badge bg-danger">LATE</span>' 
                : '<span class="badge bg-success">On Time</span>';
            
            const fileLink = `<a href="${sub.file_url}" target="_blank" class="btn btn-sm btn-outline-primary">Download File</a>`;
            
            // Grade Input
            const currentGrade = sub.grade !== null ? sub.grade : '';
            const gradeInput = `<input type="number" id="grade-${sub.id}" class="form-control form-control-sm" style="width: 80px" value="${currentGrade}" min="0" max="100">`;
            
            const saveBtn = `<button onclick="saveGrade(${sub.id})" class="btn btn-sm btn-primary">Save</button>`;

            tbody.innerHTML += `
                <tr>
                    <td class="ps-4 fw-bold">${sub.student.name}<br><small class="text-muted">${sub.student.email}</small></td>
                    <td>${fileLink}</td>
                    <td>${date}</td>
                    <td>${lateBadge}</td>
                    <td>${gradeInput}</td>
                    <td>${saveBtn}</td>
                </tr>
            `;
        });
    }

    async function saveGrade(submissionId) {
        const gradeVal = document.getElementById(`grade-${submissionId}`).value;
        if (gradeVal === '' || gradeVal < 0 || gradeVal > 100) {
            alert("Please enter a valid grade between 0 and 100");
            return;
        }

        try {
            await axios.post(`/submissions/${submissionId}/grade`, {
                grade: gradeVal,
                feedback: "Graded via Teacher Dashboard" // Optional feedback field
            });
            alert("Grade saved!");
        } catch (err) {
            alert("Failed to save grade");
        }
    }
</script>
@endpush
@endsection