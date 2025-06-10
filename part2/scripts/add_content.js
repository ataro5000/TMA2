// Helper function to toggle fields
function toggleFields(primaryField, secondaryField) {
  if (!primaryField || !secondaryField) return;

  if (primaryField.value.trim()) {
    secondaryField.disabled = true;
  } else {
    secondaryField.disabled = false;
  }

  if (secondaryField.value.trim()) {
    primaryField.disabled = true;
  } else {
    primaryField.disabled = false;
  }
}

// Toggle course fields
function toggleCourseFields() {
  const courseSelect = document.getElementById('course_id');
  const newCourseInput = document.getElementById('new_course_name');
  toggleFields(courseSelect, newCourseInput);
}

// Toggle unit fields
function toggleUnitFields() {
  const unitSelect = document.getElementById('unit_id');
  const newUnitInput = document.getElementById('new_unit_name');
  toggleFields(unitSelect, newUnitInput);
}

// Toggle title fields
function toggleTitleFields() {
  const existingTitle = document.getElementById('existing_title');
  const newTitle = document.getElementById('new_title');
  toggleFields(existingTitle, newTitle);
}

// Toggle content fields based on content type
function toggleContentFields() {
  const contentTypeElem = document.getElementById('content_type');
  if (!contentTypeElem) return;

  const contentType = contentTypeElem.value;
  const lessonFields = document.getElementById('lessonFields');
  const quizFields = document.getElementById('quizFields');
  const mediaFields = document.getElementById('mediaFields');
  const mediaFileInput = document.getElementById('media_file');
  const contentInfo = document.getElementById('content_info');
  const quizQuestion = document.getElementById('quiz_question');
  const quizAnswers = [
    document.getElementById('quiz_answer_1'),
    document.getElementById('quiz_answer_2'),
    document.getElementById('quiz_answer_3'),
    document.getElementById('quiz_answer_4')
  ];

  // Hide all fields and remove required attributes
  [lessonFields, quizFields, mediaFields].forEach(field => {
    if (field) field.style.display = 'none';
  });
  [mediaFileInput, contentInfo, quizQuestion, ...quizAnswers].forEach(field => {
    if (field) field.required = false;
  });

  // Show and require relevant fields based on content type
  if (contentType === 'lesson' && lessonFields && contentInfo) {
    lessonFields.style.display = 'block';
    contentInfo.required = true;
  } else if (contentType === 'quiz' && quizFields && quizQuestion) {
    quizFields.style.display = 'block';
    quizQuestion.required = true;
    quizAnswers.forEach(field => {
      if (field) field.required = true;
    });
  } else if (['video', 'picture', 'audio'].includes(contentType) && mediaFields && mediaFileInput) {
    mediaFields.style.display = 'block';
    mediaFileInput.accept = contentType === 'video' ? 'video/*' :
      contentType === 'picture' ? 'image/*' :
        'audio/*';
  }
}

// Validation for form submission
function validateForm(formId, primaryFieldId, secondaryFieldId, errorMessage) {
  const form = document.getElementById(formId);
  const primaryField = document.getElementById(primaryFieldId);
  const secondaryField = document.getElementById(secondaryFieldId);

  if (!form || !primaryField || !secondaryField) return;

  form.addEventListener('submit', (e) => {
    if (!primaryField.value.trim() && !secondaryField.value.trim()) {
      e.preventDefault();
      alert(errorMessage);
    }
  });
}

// Initialize all functionality on page load
document.addEventListener('DOMContentLoaded', () => {
  // Initialize field toggling
  const courseSelect = document.getElementById('course_id');
  const newCourseInput = document.getElementById('new_course_name');
  const unitSelect = document.getElementById('unit_id');
  const newUnitInput = document.getElementById('new_unit_name');
  const existingTitle = document.getElementById('existing_title');
  const newTitle = document.getElementById('new_title');
  const contentTypeElem = document.getElementById('content_type');

  if (courseSelect && newCourseInput) {
    courseSelect.addEventListener('change', toggleCourseFields);
    newCourseInput.addEventListener('input', toggleCourseFields);
    toggleCourseFields(); // Initialize state
  }

  if (unitSelect && newUnitInput) {
    unitSelect.addEventListener('change', toggleUnitFields);
    newUnitInput.addEventListener('input', toggleUnitFields);
    toggleUnitFields(); // Initialize state
  }

  if (existingTitle && newTitle) {
    existingTitle.addEventListener('change', toggleTitleFields);
    newTitle.addEventListener('input', toggleTitleFields);
    toggleTitleFields(); // Initialize state
  }

  if (contentTypeElem) {
    contentTypeElem.addEventListener('change', toggleContentFields);
    toggleContentFields(); // Initialize state
  }

  // Initialize form validation
  validateForm('course-form', 'course_id', 'new_course_name', 'Please select an existing course or enter a new course name.');
  validateForm('unit-form', 'unit_id', 'new_unit_name', 'Please select an existing unit or enter a new unit name.');
});