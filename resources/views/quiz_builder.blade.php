@extends('layout')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-10">
        
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <a href="#" onclick="history.back()" class="text-decoration-none">&larr; Back to Course</a>
            <div class="text-end">
                <h2 class="mb-0" id="displayTitle">Loading...</h2>
                <span id="displayMeta" class="badge bg-secondary">...</span>
                <button onclick="editDetails()" class="btn btn-sm btn-link text-decoration-none mt-1 d-block">Edit Details</button>
                <button onclick="deleteThisQuiz()" class="btn btn-sm btn-outline-danger mt-1">Delete Quiz</button>
            </div>
        </div>

        <!-- 2. ADD QUESTION FORM -->
        <div class="card mb-4 bg-light">
            <div class="card-body">
                <h5 class="card-title text-primary">+ Add New Question</h5>
                <!-- ADDED autocomplete="off" -->
                <form id="addQuestionForm" autocomplete="off">
                    <div class="mb-3">
                        <textarea id="newQText" class="form-control" placeholder="Type question here..." required rows="2" autocomplete="off"></textarea>
                    </div>
                    
                    <div class="row g-2 mb-3">
                        <!-- Options 1-4 -->
                        <div class="col-md-6 input-group">
                            <div class="input-group-text"><input type="radio" name="correctOpt" value="0" required></div>
                            <input type="text" class="form-control" name="opts" placeholder="Option 1" required autocomplete="off">
                        </div>
                        <div class="col-md-6 input-group">
                            <div class="input-group-text"><input type="radio" name="correctOpt" value="1"></div>
                            <input type="text" class="form-control" name="opts" placeholder="Option 2" required autocomplete="off">
                        </div>
                        <div class="col-md-6 input-group">
                            <div class="input-group-text"><input type="radio" name="correctOpt" value="2"></div>
                            <input type="text" class="form-control" name="opts" placeholder="Option 3">
                        </div>
                        <div class="col-md-6 input-group">
                            <div class="input-group-text"><input type="radio" name="correctOpt" value="3"></div>
                            <input type="text" class="form-control" name="opts" placeholder="Option 4">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-outline-primary">Add Question</button>
                </form>
            </div>
        </div>

        <!-- 3. QUESTION LIST -->
        <div id="questions-container">
            <!-- Questions injected here -->
        </div>

    </div>
</div>

@push('scripts')
<script>
    const quizId = Number("{{ $id }}");
    let quizData = null;

    document.addEventListener('DOMContentLoaded', () => {
        loadQuiz();
    });

    // Helper to handle API errors consistently
    function handleApiError(err, defaultMsg) {
        console.error(err);
        if (err.response && err.response.status === 401) {
            alert("Session expired. Please login again.");
            localStorage.clear();
            window.location.href = '/';
            return;
        }
        if (err.response && err.response.status === 403) {
            alert("Unauthorized: You do not have permission to edit this quiz.");
            return;
        }
        const msg = (err.response && err.response.data && err.response.data.message) || defaultMsg;
        alert(msg);
    }

    async function loadQuiz() {
        try {
            // Relies on global Axios Authorization header from layout.blade.php
            const res = await axios.get(`/quizzes/${quizId}`);
            quizData = res.data;
            
            // Render Header Info
            document.getElementById('displayTitle').innerText = quizData.title;
            const timeInfo = quizData.time_limit > 0 ? `${quizData.time_limit} Mins` : 'No Time Limit';
            document.getElementById('displayMeta').innerText = `Pass: ${quizData.passing_score}% | Time: ${timeInfo}`;
            
            renderQuestions(quizData.questions);
        } catch (err) { 
            handleApiError(err, 'Failed to load quiz');
        }
    }

    // Edit Quiz Settings
    async function editDetails() {
        if (!quizData) return;

        const newTitle = prompt("Quiz Title:", quizData.title);
        if (newTitle === null) return; // Cancelled
        
        const newTime = prompt("Time Limit (Minutes) - 0 for none:", quizData.time_limit);
        if (newTime === null) return;
        
        const newScore = prompt("Passing Score (%):", quizData.passing_score);
        if (newScore === null) return;

        try {
            await axios.put(`/quizzes/${quizId}`, {
                title: newTitle,
                time_limit: parseInt(newTime) || 0,
                passing_score: parseInt(newScore) || 50
            });
            
            // Reload to show new values
            loadQuiz(); 
        } catch (err) { 
            handleApiError(err, 'Failed to update settings');
        }
    }

    async function deleteThisQuiz() {
        if(!confirm("Are you sure you want to delete this ENTIRE quiz?")) return;
        try {
            await axios.delete(`/quizzes/${quizId}`);
            alert("Quiz Deleted.");
            window.history.back(); // Go back to course
        } catch (err) {
            handleApiError(err, 'Failed to delete quiz');
        }
    }

    document.getElementById('addQuestionForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const inputs = document.getElementsByName('opts');
        const correctRadio = document.querySelector('input[name="correctOpt"]:checked');
        
        if (!correctRadio) return alert("Please select a correct answer.");
        const correctIndex = correctRadio.value;
        
        const options = [];
        for(let i=0; i<4; i++) {
            if (inputs[i].value.trim() !== '') {
                options.push({
                    text: inputs[i].value,
                    is_correct: (i == correctIndex)
                });
            }
        }

        if (options.length < 2) return alert('At least 2 options required');
        const correctOptionExists = options.some(o => o.is_correct);
        if (!correctOptionExists) return alert("The selected correct answer cannot be empty.");

        try {
            await axios.post(`/quizzes/${quizId}/questions`, {
                question_text: document.getElementById('newQText').value,
                options: options
            });
            
            // Reset Form
            document.getElementById('addQuestionForm').reset();
            // Reset radio to first option
            const radios = document.getElementsByName('correctOpt');
            if(radios.length > 0) radios[0].checked = true;
            
            loadQuiz(); 
        } catch (err) { 
            handleApiError(err, 'Failed to add question');
        }
    });

    function renderQuestions(questions) {
        const container = document.getElementById('questions-container');
        
        if (!questions || questions.length === 0) {
            container.innerHTML = '<div class="alert alert-info">No questions yet. Add one above!</div>';
            return;
        }

        container.innerHTML = `<h5 class="mb-3 text-muted">Questions (${questions.length})</h5>`;
        
        questions.forEach((q, i) => {
            let optsHtml = '';
            q.options.forEach(o => {
                const cls = o.is_correct ? 'bg-success text-white' : 'bg-white text-dark';
                optsHtml += `<span class="badge ${cls} me-1 p-2 fw-normal border">${o.option_text}</span>`;
            });

            const html = `
                <div class="card mb-3 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <h6 class="card-title fw-bold mb-2">Q${i+1}: ${q.question_text}</h6>
                            <button onclick="deleteQuestion(${q.id})" class="btn btn-sm btn-outline-danger" title="Delete Question">&times;</button>
                        </div>
                        <div class="mt-2">${optsHtml}</div>
                    </div>
                </div>
            `;
            container.innerHTML += html;
        });
    }

    async function deleteQuestion(id) {
        if(confirm('Delete this question?')) {
            try { 
                await axios.delete(`/questions/${id}`); 
                loadQuiz(); 
            }
            catch(err) { handleApiError(err, 'Failed to delete question'); }
        }
    }
</script>
@endpush
@endsection