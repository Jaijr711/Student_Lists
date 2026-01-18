// Student Data Array
let students = loadStudents();
let nextId = students.length > 0 ? Math.max(...students.map(s => s.id)) + 1 : 1;
let editingId = null;
let deleteId = null;
let pendingStudentData = null;

// DOM Elements - Views
const listSection = document.getElementById('listSection');
const formSection = document.getElementById('formSection');
const deleteModal = document.getElementById('deleteModal');
const warningModal = document.getElementById('warningModal');
const deleteStudentNameSpan = document.getElementById('deleteStudentName');
const loadingOverlay = document.getElementById('loadingOverlay');
const loadingState = document.getElementById('loadingState');
const successState = document.getElementById('successState');
const successStudentName = document.getElementById('successStudentName');

// DOM Elements - Buttons & Inputs
const addNewBtn = document.getElementById('addNewBtn');
const closeFormBtn = document.getElementById('closeFormBtn');
const studentForm = document.getElementById('studentForm');
const submitBtn = document.getElementById('submitBtn');
const cancelBtn = document.getElementById('cancelBtn');
const studentTableBody = document.getElementById('studentTableBody');
const studentIdInput = document.getElementById('studentId');
const studentCountSpan = document.getElementById('studentCount');
const emptyStateDiv = document.getElementById('emptyState');
const searchInput = document.getElementById('searchInput');
const formTitle = document.getElementById('formTitle');
const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
const continueWarningBtn = document.getElementById('continueWarningBtn');
const cancelWarningBtn = document.getElementById('cancelWarningBtn');

// Form Inputs
const lastNameInput = document.getElementById('lastName');
const firstNameInput = document.getElementById('firstName');
const middleNameInput = document.getElementById('middleName');
const birthdateInput = document.getElementById('birthdate');
const courseInput = document.getElementById('course');
const yearLevelInput = document.getElementById('yearLevel');

// Event Listeners
studentForm.addEventListener('submit', handleFormSubmit);
cancelBtn.addEventListener('click', showList); // Cancel just goes back to list
closeFormBtn.addEventListener('click', showList); // Close X also goes back
addNewBtn.addEventListener('click', showForm); // Open Form
searchInput.addEventListener('input', handleSearch);
confirmDeleteBtn.addEventListener('click', confirmDelete);
cancelDeleteBtn.addEventListener('click', closeDeleteModal);
continueWarningBtn.addEventListener('click', confirmDuplicateSave);
cancelWarningBtn.addEventListener('click', closeWarningModal);

// Close Modal on Background Click
formSection.addEventListener('click', (e) => {
    if (e.target === formSection) showList();
});
deleteModal.addEventListener('click', (e) => {
    if (e.target === deleteModal) closeDeleteModal();
});
warningModal.addEventListener('click', (e) => {
    if (e.target === warningModal) closeWarningModal();
});

// Close Modal on Escape Key
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        if (warningModal.style.display === 'flex') {
            closeWarningModal();
            return; // Stop propagation/handling for lower modals
        }
        if (deleteModal.style.display === 'flex') closeDeleteModal();
        if (formSection.style.display === 'flex') showList();
    }
});

/**
 * Switch to Form View (Floating Modal)
 */
function showForm() {
    // Keep list visible but blurred
    listSection.style.display = 'block'; 
    listSection.classList.add('blur-background');
    
    // Show form as flex overlay
    formSection.style.display = 'flex';
    
    // If not editing, reset form state
    if (!editingId) {
        resetFormUI();
    }
}

/**
 * Switch to List View
 */
function showList() {
    // Hide form
    formSection.style.display = 'none';
    
    // Unblur list
    listSection.classList.remove('blur-background');
    listSection.style.display = 'block';
    
    resetFormUI(); // Always clean up when leaving form
}

/**
 * Handles Form Submission (Add or Update)
 */
function handleFormSubmit(e) {
    e.preventDefault();

    const lastName = lastNameInput.value.trim();
    const firstName = firstNameInput.value.trim();
    const middleName = middleNameInput.value.trim();
    const birthdate = birthdateInput.value;
    const course = courseInput.value;
    const yearLevel = yearLevelInput.value;

    if (!lastName || !firstName || !birthdate || !course || !yearLevel) {
        alert("Please fill in all required fields.");
        return;
    }

    const studentData = {
        lastName,
        firstName,
        middleName,
        birthdate,
        course,
        yearLevel
    };

    // Check for Duplicates
    if (checkDuplicate(firstName, lastName, editingId)) {
        pendingStudentData = studentData;
        showWarningModal();
        return;
    }

    saveStudentData(studentData);
}

/**
 * Shows the duplicate warning modal
 */
function showWarningModal() {
    warningModal.style.display = 'flex';
}

/**
 * Closes the duplicate warning modal
 */
function closeWarningModal() {
    warningModal.style.display = 'none';
    pendingStudentData = null;
}

/**
 * Confirms save from warning modal
 */
function confirmDuplicateSave() {
    if (pendingStudentData) {
        saveStudentData(pendingStudentData);
    }
    closeWarningModal();
}

/**
 * Saves student data (Add or Update) and closes form
 */
function saveStudentData(data) {
    if (editingId) {
        // Update Existing Student
        updateStudent(editingId, data);
        
        // Show Loading Animation for Updates
        showLoading(data.firstName + ' ' + data.lastName, 'update');
    } else {
        // Add New Student
        addStudent({
            id: nextId++,
            ...data
        });
        
        // Show Loading Animation for New Registrations
        showLoading(data.firstName + ' ' + data.lastName, 'register');
    }
}

/**
 * Shows Loading and Success Animation
 */
function showLoading(name, type = 'register') {
    // Hide Form immediately
    formSection.style.display = 'none';
    deleteModal.style.display = 'none';
    
    // Show Overlay
    loadingOverlay.style.display = 'flex';
    loadingState.style.display = 'block';
    successState.style.display = 'none';
    
    // Update Text based on type
    const successTitle = successState.querySelector('p');
    if (type === 'delete') {
        successTitle.textContent = 'Successfully Deleted';
    } else if (type === 'update') {
        successTitle.textContent = 'Successfully Updated';
    } else {
        successTitle.textContent = 'Successfully Registered';
    }
    
    // Simulate Loading delay
    setTimeout(() => {
        // Show Success
        loadingState.style.display = 'none';
        successState.style.display = 'block';
        successStudentName.textContent = name;
        
        // Wait then Close
        setTimeout(() => {
            loadingOverlay.style.display = 'none';
            showList();
            searchInput.value = '';
            renderTable();
        }, 1500); // Show success for 1.5s
        
    }, 1500); // Show loading for 1.5s
}

/**
 * Checks for duplicate students (same First and Last name)
 */
function checkDuplicate(firstName, lastName, excludeId = null) {
    return students.some(student => {
        // Skip the student currently being edited
        if (excludeId && String(student.id) === String(excludeId)) return false;
        
        return student.firstName.toLowerCase() === firstName.toLowerCase() && 
               student.lastName.toLowerCase() === lastName.toLowerCase();
    });
}

/**
 * Adds a new student to the array
 */
function addStudent(student) {
    students.push(student);
    sortStudents();
    saveToLocalStorage();
}

/**
 * Updates an existing student
 */
function updateStudent(id, updatedData) {
    // Use String comparison to be safe against type mismatches
    const index = students.findIndex(s => String(s.id) === String(id));
    if (index !== -1) {
        students[index] = { ...students[index], ...updatedData };
        sortStudents();
        saveToLocalStorage();
    }
}

/**
 * Opens Delete Confirmation Modal
 */
function deleteStudent(id) {
    const student = students.find(s => String(s.id) === String(id));
    if (!student) return;

    deleteId = id;
    deleteStudentNameSpan.textContent = `${student.firstName} ${student.lastName}`;
    
    listSection.style.display = 'block'; 
    listSection.classList.add('blur-background');
    deleteModal.style.display = 'flex';
}

/**
 * Executes Deletion
 */
function confirmDelete() {
    if (deleteId) {
        // Get name for display
        const student = students.find(s => String(s.id) === String(deleteId));
        const name = student ? `${student.firstName} ${student.lastName}` : '';
        
        // Perform Deletion logic
        students = students.filter(s => String(s.id) !== String(deleteId));
        saveToLocalStorage();
        
        // Show Loading Animation
        showLoading(name, 'delete');
    }
    closeDeleteModal();
}

/**
 * Sorts students alphabetically by Last Name
 */
function sortStudents() {
    students.sort((a, b) => {
        const nameA = a.lastName.toLowerCase();
        const nameB = b.lastName.toLowerCase();
        if (nameA < nameB) return -1;
        if (nameA > nameB) return 1;
        return 0;
    });
}

/**
 * Closes Delete Modal
 */
function closeDeleteModal() {
    deleteId = null;
    deleteModal.style.display = 'none';
    
    // Only remove blur if form is not also open (though they shouldn't be open together)
    if (formSection.style.display !== 'flex') {
        listSection.classList.remove('blur-background');
    }
}

/**
 * Prepares the form for editing a student
 */
function editStudent(id) {
    const student = students.find(s => String(s.id) === String(id));
    if (!student) return;

    // Populate Form
    studentIdInput.value = student.id;
    lastNameInput.value = student.lastName;
    firstNameInput.value = student.firstName;
    middleNameInput.value = student.middleName;
    birthdateInput.value = student.birthdate;
    courseInput.value = student.course;
    yearLevelInput.value = student.yearLevel;

    // Update UI State for Edit
    editingId = id;
    formTitle.innerHTML = '<i class="bi bi-pencil-square me-2"></i>Edit Student Details';
    submitBtn.innerHTML = '<i class="bi bi-check-circle me-2"></i>Update Student';
    submitBtn.classList.replace('btn-school-primary', 'btn-warning');
    submitBtn.classList.add('text-dark');
    
    // Show Form
    listSection.style.display = 'block';
    listSection.classList.add('blur-background');
    formSection.style.display = 'flex';
}

/**
 * Resets the form UI to default "Add" state
 */
function resetFormUI() {
    studentForm.reset();
    editingId = null;
    studentIdInput.value = ''; // Clear displayed ID
    
    formTitle.innerHTML = '<i class="bi bi-person-plus-fill me-2"></i>Register New Student';
    submitBtn.innerHTML = '<i class="bi bi-save me-2"></i>Save Student';
    submitBtn.classList.replace('btn-warning', 'btn-school-primary');
    submitBtn.classList.remove('text-dark');
}

/**
 * Calculates age based on birthdate
 */
function calculateAge(birthdateString) {
    const birthDate = new Date(birthdateString);
    const today = new Date();
    let age = today.getFullYear() - birthDate.getFullYear();
    const m = today.getMonth() - birthDate.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
        age--;
    }
    return age;
}

/**
 * Handles Search functionality
 */
function handleSearch() {
    const query = searchInput.value.toLowerCase().trim();
    
    if (!query) {
        renderTable(students);
        return;
    }

    const filteredStudents = students.filter(student => {
        const fullName = `${student.lastName} ${student.firstName} ${student.middleName}`.toLowerCase();
        const idStr = student.id.toString();
        return fullName.includes(query) || idStr.includes(query);
    });

    renderTable(filteredStudents);
}

/**
 * Renders the table of students
 */
function renderTable(data = students) {
    studentTableBody.innerHTML = '';
    studentCountSpan.textContent = data.length;

    if (data.length === 0) {
        // Show different message if it's a search result vs empty DB
        if (students.length > 0 && searchInput.value) {
            studentTableBody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">No matching results found.</td></tr>';
        } else {
            emptyStateDiv.style.display = 'block';
        }
        return;
    } else {
        emptyStateDiv.style.display = 'none';
    }

    data.forEach(student => {
        const age = calculateAge(student.birthdate);
        const fullName = `<strong>${student.lastName}</strong>, ${student.firstName} ${student.middleName}`;
        const isMinor = age < 18;
        
        const row = document.createElement('tr');
        if (isMinor) {
            row.classList.add('table-minor');
        }

        let ageDisplay = age;
        if (isMinor) {
            ageDisplay += ` <span class="badge badge-minor">Minor</span>`;
        }

        row.innerHTML = `
            <td class="ps-3 text-muted fw-bold d-none d-sm-table-cell">#${student.id}</td>
            <td>${fullName}</td>
            <td class="d-none d-sm-table-cell">${ageDisplay}</td>
            <td><span class="badge bg-light text-dark border">${student.course}</span></td>
            <td class="d-none d-sm-table-cell">${student.yearLevel}</td>
            <td class="text-end pe-3">
                <button class="btn btn-action btn-outline-primary me-1" onclick="editStudent(${student.id})" title="Edit">
                    <i class="bi bi-pencil-fill"></i>
                </button>
                <button class="btn btn-action btn-outline-danger" onclick="deleteStudent(${student.id})" title="Delete">
                    <i class="bi bi-trash-fill"></i>
                </button>
            </td>
        `;
        studentTableBody.appendChild(row);
    });
}

/**
 * Loads students from LocalStorage
 */
function loadStudents() {
    const stored = localStorage.getItem('studentData');
    return stored ? JSON.parse(stored) : [];
}

/**
 * Saves students to LocalStorage
 */
function saveToLocalStorage() {
    localStorage.setItem('studentData', JSON.stringify(students));
}

// Initial Render
renderTable();
