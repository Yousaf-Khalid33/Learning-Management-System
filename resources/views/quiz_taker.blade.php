@extends('layout')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        
        <!-- Header & Timer -->
        <div class="d-flex justify-content-between align-items-center mb-4 sticky-top bg-white py-3 border-bottom shadow-sm px-3">
            <h4 id="quizTitle" class="m-0">Loading Quiz...</h4>
            
            <div id="timerContainer" class="d-flex align-items-center">
                <div id="timerBadge" class="badge bg-secondary fs-5 d-none d-flex align-items-center gap-2">
                    <span>⏳</span> <span id="timer" class="font-monospace">00:00</span>
                </div>
                <span id="noLimitBadge" class="badge bg-light text-dark border d-none">No Time Limit</span>
            </div>
        </div>

        <!-- QUIZ FORM -->
        <div id="quiz-container">
            <div class="text-center p-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 text-muted">Fetching questions...</p>
            </div>
        </div>

        <!-- RESULT VIEW (Hidden by default) -->
        <div id="result-container" class="d-none text-center p-5 card shadow-sm">
            <div class="mb-4" id="emoji-container"></div>
            <h2 class="mb-3" id="result-title">Quiz Completed!</h2>
            <div id="scoreAlert" class="alert d-inline-block px-5">
                <h1 class="display-4 fw-bold mb-0" id="finalScore">0</h1>
                <small id="scoreLabel">PERCENTAGE</small>
            </div>
            <div class="mt-4">
                <a href="/student-dashboard" class="btn btn-primary">Return to Dashboard</a>
            </div>
        </div>

    </div>
</div>

@push('scripts')
<script>
    // Robust ID parsing
    const quizId = Number("{{ $id }}");
    let timeLeft = null; 
    let timerInterval = null;
    let answers = {};

    document.addEventListener('DOMContentLoaded', () => {
        // Validate Token before starting
        const token = localStorage.getItem('token');
        if (!token) {
            alert("You are logged out. Please log in again.");
            window.location.href = '/';
            return;
        }
        loadQuiz();
    });

    async function loadQuiz() {
        try {
            // Using global axios defaults from layout.blade.php
            const res = await axios.get(`/quizzes/${quizId}`);
            const quiz = res.data;

            // 1. Handle Already Attempted
            if (quiz.already_attempted) {
                showResult(quiz.score);
                return;
            }

            // 2. Setup Timer Logic
            // Handle various backend formats for seconds
            let seconds = null;
            if (quiz.remaining_seconds !== undefined && quiz.remaining_seconds !== null) {
                seconds = parseInt(quiz.remaining_seconds);
            } 
            else if (quiz.time_limit && quiz.time_limit > 0) {
                // Fallback if backend sent raw minutes
                seconds = quiz.time_limit * 60;
            }

            if (seconds !== null && !isNaN(seconds) && seconds > 0) {
                startTimer(seconds);
            } else {
                document.getElementById('noLimitBadge').classList.remove('d-none');
            }

            // 3. Render Quiz
            document.getElementById('quizTitle').innerText = quiz.title;
            renderQuestions(quiz.questions);

        } catch (err) {
            console.error(err);
            const msg = (err.response && err.response.data && err.response.data.message) || 'Failed to load quiz. Please check your connection.';
            
            // Handle specific auth errors
            if (err.response && err.response.status === 401) {
                alert("Session expired. Please login again.");
                window.location.href = '/';
                return;
            }

            alert(msg);
            window.history.back();
        }
    }

    function startTimer(seconds) {
        timeLeft = seconds;
        const badge = document.getElementById('timerBadge');
        badge.classList.remove('d-none');
        document.getElementById('noLimitBadge').classList.add('d-none');
        
        updateTimerDisplay();

        timerInterval = setInterval(() => {
            timeLeft--;
            updateTimerDisplay();

            if (timeLeft <= 0) {
                clearInterval(timerInterval);
                // No alert, just submit
                submitQuiz();
            }
        }, 1000);
    }

    function updateTimerDisplay() {
        if (timeLeft < 0) timeLeft = 0;
        const mins = Math.floor(timeLeft / 60);
        const secs = timeLeft % 60;
        const display = `${mins}:${secs < 10 ? '0' : ''}${secs}`;
        
        const el = document.getElementById('timer');
        if(el) el.innerText = display;
        
        const badge = document.getElementById('timerBadge');
        if(badge) {
            if (timeLeft < 60) {
                badge.classList.remove('bg-secondary');
                badge.classList.add('bg-danger');
            } else {
                badge.classList.add('bg-secondary');
                badge.classList.remove('bg-danger');
            }
        }
    }

    function renderQuestions(questions) {
        const container = document.getElementById('quiz-container');
        
        if (!questions || questions.length === 0) {
            container.innerHTML = `<div class="alert alert-warning text-center">This quiz has no questions yet.</div>
            <div class="text-center mt-3"><a href="/student-dashboard" class="btn btn-outline-secondary">Go Back</a></div>`;
            return;
        }

        let html = '';
        questions.forEach((q, index) => {
            let optionsHtml = '';
            q.options.forEach(opt => {
                optionsHtml += `
                    <div class="form-check mb-2 p-3 border rounded bg-white">
                        <input class="form-check-input" type="radio" name="q_${q.id}" id="opt_${opt.id}" value="${opt.id}" onchange="selectAnswer(${q.id}, ${opt.id})">
                        <label class="form-check-label w-100 ps-2" for="opt_${opt.id}" style="cursor: pointer">
                            ${opt.option_text}
                        </label>
                    </div>
                `;
            });

            html += `
                <div class="card mb-4 border-0 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title mb-3"><span class="badge bg-primary me-2">${index + 1}</span> ${q.question_text}</h5>
                        ${optionsHtml}
                    </div>
                </div>
            `;
        });

        html += `
            <div class="d-grid gap-2 mb-5">
                <button onclick="confirmSubmit()" class="btn btn-success btn-lg">Submit Quiz</button>
            </div>
        `;

        container.innerHTML = html;
    }

    function selectAnswer(qId, optId) {
        answers[qId] = optId;
    }

    function confirmSubmit() {
        if (confirm("Are you sure you want to finish this quiz?")) {
            submitQuiz();
        }
    }

    async function submitQuiz() {
        if (timerInterval) clearInterval(timerInterval);

        const formattedAnswers = Object.keys(answers).map(qId => ({
            question_id: qId,
            selected_option_id: answers[qId]
        }));

        try {
            // Using global axios defaults
            const res = await axios.post(`/quizzes/${quizId}/submit`, {
                answers: formattedAnswers
            });
            showResult(res.data.score);
        } catch (err) {
            const msg = (err.response && err.response.data && err.response.data.message) || "Submission failed";
            alert(msg);
        }
    }

    function showResult(score) {
        document.getElementById('quiz-container').classList.add('d-none');
        document.getElementById('timerContainer').classList.add('d-none');
        
        const emojiContainer = document.getElementById('emoji-container');
        const resultTitle = document.getElementById('result-title');
        const scoreDisplay = document.getElementById('finalScore');
        const scoreLabel = document.getElementById('scoreLabel');
        const scoreAlert = document.getElementById('scoreAlert');

        scoreDisplay.innerText = score + '%';
        scoreLabel.innerText = 'PERCENTAGE';

        if (score >= 50) {
            emojiContainer.innerHTML = '<span style="font-size: 4rem;">🎉</span>';
            resultTitle.innerText = 'Quiz Passed!';
            resultTitle.className = 'mb-3 text-success fw-bold';
            scoreAlert.className = 'alert alert-success d-inline-block px-5';
        } else {
            emojiContainer.innerHTML = ''; 
            resultTitle.innerText = 'Oops you have failed the quiz';
            resultTitle.className = 'mb-3 text-danger fw-bold';
            scoreAlert.className = 'alert alert-danger d-inline-block px-5';
        }
        
        document.getElementById('result-container').classList.remove('d-none');
        
        // Scroll to top to ensure result is seen
        window.scrollTo(0, 0);
    }
</script>
@endpush
@endsection