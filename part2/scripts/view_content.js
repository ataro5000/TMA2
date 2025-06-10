
// Function to toggle the visibility of the content body
function showEditForm(contentId) {
    document.getElementById('edit-form-' + contentId).style.display = 'block';
    document.getElementById('content-body-' + contentId).style.display = 'none';
}
function hideEditForm(contentId) {
    document.getElementById('edit-form-' + contentId).style.display = 'none';
    document.getElementById('content-body-' + contentId).style.display = 'block';
}
// Function to toggle the visibility of the quiz edit form
function showEditQuizForm(contentId) {
    document.getElementById('edit-quiz-form-' + contentId).style.display = 'block';
}
function hideEditQuizForm(contentId) {
    document.getElementById('edit-quiz-form-' + contentId).style.display = 'none';
}

//Quiz AJAX for submission
document.addEventListener('DOMContentLoaded', function () {
    // Quiz answer checking on radio change
    document.querySelectorAll('.quiz-form').forEach(form => {
        form.querySelectorAll('input[type="radio"]').forEach(radio => {
            radio.addEventListener('change', function () {
                const answer_id = this.value;
                fetch('../php/check_quiz_answer.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ answer_id: answer_id })
                })
                    .then(res => res.json())
                    .then(data => {
                        const feedback = form.querySelector('.quiz-feedback');
                        if (data.correct) {
                            feedback.textContent = "Correct!";
                            feedback.style.color = "green";
                        } else {
                            feedback.textContent = "Incorrect!";
                            feedback.style.color = "red";
                        }
                        feedback.style.display = "block";
                    })
                    .catch(() => {
                        const feedback = form.querySelector('.quiz-feedback');
                        feedback.textContent = "An error occurred.";
                        feedback.style.color = "red";
                        feedback.style.display = "block";
                    });
            });
        });
    });

    // Mark complete button functionality
    const markCompleteBtn = document.getElementById('markCompleteBtn');
    if (markCompleteBtn) {
        const contentIds = JSON.parse(markCompleteBtn.getAttribute('data-content-ids'));
        markCompleteBtn.addEventListener('click', function () {
            fetch('../php/mark_complete.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `content_ids=${JSON.stringify(contentIds)}`
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.querySelectorAll('.mark-complete-btn').forEach(btn => {
                            btn.textContent = '✓ Completed';
                            btn.classList.add('completed');
                            btn.disabled = true;
                        });
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while marking as complete');
                });
        });
    }

    // move content form submission
    document.querySelectorAll('.move-content-btn').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const contentId = this.getAttribute('data-content-id');
            const direction = this.getAttribute('data-direction');
            fetch('../php/move_content.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `content_id=${encodeURIComponent(contentId)}&direction=${encodeURIComponent(direction)}`
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        location.reload(); // Reload to reflect new order
                    } else {
                        alert(data.message || 'Move failed');
                    }
                });
        });
    });
});

