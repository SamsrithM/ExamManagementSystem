<?php
// seating_arrangement.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Classroom Seating Arrangement System</title>
<style>
/* ---------- Global Styles ---------- */
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #ffffff;
    margin: 0;
    padding: 0;
}

h1 {
    margin: 0;
}

/* ---------- Professional Heading ---------- */
.main-heading {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    font-size: 3rem;
    font-weight: 800;
    text-align: center;
    color: #1a73e8;
    text-shadow: 1px 1px 4px rgba(0,0,0,0.1);
    margin: 30px 0 40px 0;
    position: relative;
    background: none;
    -webkit-background-clip: initial;
    -webkit-text-fill-color: initial;
}

.main-heading::after {
    content: '';
    display: block;
    width: 180px;
    height: 4px;
    background: #1a73e8;
    margin: 10px auto 0 auto;
    border-radius: 2px;
}

/* ---------- Container ---------- */
.container {
    max-width: 1000px;
    margin: 0 auto;
    background: #ffffff;
    padding: 30px 40px;
    border-radius: 15px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.15);
}

/* ---------- Notification Box ---------- */
#notification {
    display: none;
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    padding: 20px 30px;
    border-radius: 10px;
    font-weight: 700;
    text-align: center;
    color: #fff;
    z-index: 1000;
    font-size: 1.2rem;
    box-shadow: 0 8px 20px rgba(0,0,0,0.25);
}

/* ---------- Form Styles ---------- */
.classroom-form {
    margin-bottom: 25px;
    border: 1px solid #ddd;
    padding: 20px;
    border-radius: 12px;
    background: #f9f9f9;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}

.classroom-form h3 {
    margin-top: 0;
    color: #333;
    font-size: 1.5rem;
    margin-bottom: 15px;
}

label {
    font-weight: 600;
    display: block;
    margin-top: 12px;
    color: #555;
}

input, select {
    padding: 8px 10px;
    width: 220px;
    border-radius: 6px;
    border: 1px solid #ccc;
    margin-bottom: 12px;
    transition: all 0.3s ease;
}

input:focus, select:focus {
    outline: none;
    border-color: #1a73e8;
    box-shadow: 0 0 6px rgba(26, 115, 232, 0.3);
}

/* ---------- Buttons ---------- */
button {
    padding: 10px 18px;
    color: #fff;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s ease;
    margin-top: 10px;
}

#generateClassroomBtn {
    background: linear-gradient(to right, #1a73e8, #155fc1);
}

#generateClassroomBtn:hover {
    background: linear-gradient(to right, #155fc1, #0d3f8a);
}

.cancel-btn {
    background: linear-gradient(to right, #d9534f, #b52b27);
}

.cancel-btn:hover {
    background: linear-gradient(to right, #b52b27, #801a15);
}

.dashboard-btn {
    background: linear-gradient(to right, #28a745, #1e7e34);
    width: 100%;
    padding: 12px 0;
    font-size: 1.1rem;
    margin-top: 30px;
}

.dashboard-btn:hover {
    background: linear-gradient(to right, #1e7e34, #145a22);
}

/* ---------- Table Styles ---------- */
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
    text-align: center;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    border-radius: 8px;
    overflow: hidden;
}

th, td {
    border: 1px solid #ccc;
    padding: 10px;
    font-weight: 600;
    color: #333;
}

th {
    background: linear-gradient(to right, #1a73e8, #155fc1);
    color: #fff;
}

.empty {
    background: #f2f2f2;
}

/* ---------- Responsive ---------- */
@media (max-width: 768px) {
    input, select {
        width: 100%;
    }
    .container {
        padding: 20px;
    }
}
</style>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
</head>
<body>
<h1 class="main-heading">Classroom Seating Arrangement System</h1>

<div class="container">

    <!-- Notification Box -->
    <div id="notification"></div>

    <div class="classroom-form">
        <h3>Select Classroom and Departments</h3>
        <label>Classroom:</label>
        <select id="classroomSelect">
            <option value="">--Select Classroom--</option>
            <option value="CS101">CS101</option>
            <option value="CS102">CS102</option>
            <option value="CS201">CS201</option>
            <option value="CS202">CS202</option>
            <option value="ME101">ME101</option>
            <option value="ME201">ME201</option>
            <option value="EC101">EC101</option>
            <option value="EC201">EC201</option>
            <option value="Krishna Seminar Hall">Krishna Seminar Hall</option>
            <option value="Computing Lab">Computing Lab</option>
        </select>
        <label>Exam Date:</label>
        <input type="date" id="examDate" required>
        <label>Exam Time:</label>
        <input type="time" id="examTime" required>
        <label>Department 1 Name (or NIL):</label>
        <input type="text" id="dept1" placeholder="">
        <label>Department 1 Students:</label>
        <input type="number" id="dept1Count" min="0" placeholder="">
        <label>Department 1 Roll Number From:</label>
        <input type="number" id="dept1RollFrom" min="1" placeholder="">

        <label>Department 2 Name (or NIL):</label>
        <input type="text" id="dept2" placeholder="">
        <label>Department 2 Students:</label>
        <input type="number" id="dept2Count" min="0" placeholder="">
        <label>Department 2 Roll Number From:</label>
        <input type="number" id="dept2RollFrom" min="1" placeholder="">

        <label>Department 3 Name (or NIL):</label>
        <input type="text" id="dept3" placeholder="">
        <label>Department 3 Students:</label>
        <input type="number" id="dept3Count" min="0" placeholder="">
        <label>Department 3 Roll Number From:</label>
        <input type="number" id="dept3RollFrom" min="1" placeholder="">
        <br>
        <button id="generateClassroomBtn">Generate Classroom</button>
        <button id="cancelBtn" class="cancel-btn">Cancel Arrangement</button>
    </div>

    <div id="output"></div>
    <!-- ---------- New Save Classrooms Button ---------- -->
    <button class="dashboard-btn" style="background: linear-gradient(to right, #ff9800, #e68900);" 
            onclick="saveClassrooms()">Save Classrooms Generated</button>

    <script>
    function saveClassrooms() {
        if (selectedClassrooms.length === 0) {
            showNotification('No classrooms generated to save!', 'error');
            return;
        }

        // Get exam date and time
        const examDate = document.getElementById('examDate').value;
        const examTime = document.getElementById('examTime').value;

        if (!examDate || !examTime) {
            showNotification('Please select exam date and time!', 'error');
            return;
        }

        // Prepare classrooms array with date and time
        const classroomsToSend = selectedClassrooms.map(name => ({
            name: name,
            date: examDate,
            time: examTime
        }));

        fetch('save_classrooms.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ classrooms: classroomsToSend })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showNotification(data.message, 'success');
                selectedClassrooms = []; // clear after saving
            } else {
                showNotification(data.message, 'error');
            }
        })
        .catch(err => {
            showNotification('Error saving classrooms!', 'error');
            console.error(err);
        });
    }

    </script>
    <!-- Return to Dashboard button -->
    <button class="dashboard-btn" id="dashboardBtn">Return to Dashboard</button>

</div>

<script>
let selectedClassrooms = [];

// ---------- Notification Function ----------
function showNotification(message, type = 'success') {
    const notification = document.getElementById('notification');
    notification.style.display = 'block';
    notification.textContent = message;
    notification.style.background = type === 'success' ? 'linear-gradient(to right, #28a745, #1e7e34)' : 'linear-gradient(to right, #d9534f, #b52b27)';
    setTimeout(() => { notification.style.display = 'none'; }, 3000);
}

    document.getElementById('dashboardBtn').addEventListener('click', (e) => {
        if (selectedClassrooms.length > 0) {
            e.preventDefault(); // Stop immediate navigation

            const save = confirm("You have unsaved classroom arrangements. Do you want to save them before leaving?");
            if (save) {
                saveClassrooms(); // Call your existing save function
                // Redirect after saving
                setTimeout(() => {
                    window.location.href = 'http://localhost/Exam_Management_System/Admin/admin_front_page.php';
                }, 500); // short delay to allow save notification
            } else {
                // Redirect without saving
                window.location.href = 'http://localhost/Exam_Management_System/Admin/admin_front_page.php';
            }
        } else {
            window.location.href = 'http://localhost/Exam_Management_System/Admin/admin_front_page.php';
        }
    });


document.getElementById('generateClassroomBtn').addEventListener('click', () => {
    const classroom = document.getElementById('classroomSelect').value;

// Department names
    const dept1 = (document.getElementById('dept1').value || '').trim().toUpperCase();
    const dept2 = (document.getElementById('dept2').value || '').trim().toUpperCase();
    const dept3 = (document.getElementById('dept3').value || '').trim().toUpperCase();

    // Student counts
    let dept1Count = parseInt(document.getElementById('dept1Count').value) || 0;
    let dept2Count = parseInt(document.getElementById('dept2Count').value) || 0;
    let dept3Count = parseInt(document.getElementById('dept3Count').value) || 0;

    // Roll number starting points
    let dept1RollFrom = parseInt(document.getElementById('dept1RollFrom').value) || 1;
    let dept2RollFrom = parseInt(document.getElementById('dept2RollFrom').value) || 1;
    let dept3RollFrom = parseInt(document.getElementById('dept3RollFrom').value) || 1;


    if (!classroom) { showNotification('Select a classroom!', 'error'); return; }
    if (selectedClassrooms.includes(classroom)) { showNotification('This classroom is already generated!', 'error'); return; }

    // ---------- Check student limits ----------
    let totalStudents = dept1Count + dept2Count;
    let limit = 60;
    if (classroom === "Computing Lab") limit = 93;
    else if (classroom === "Krishna Seminar Hall") limit = 98;

  else {
    // single-department classroom
    if ((dept1Count > 0 && dept2Count === 0) || (dept2Count > 0 && dept1Count === 0)) {
        limit = 30; // max 30 if only one department
    } else {
        limit = 60; // max 60 if two departments
    }
    }

    if (totalStudents > limit) {
    showNotification(`Total students exceed the limit of ${limit} for ${classroom}!`, 'error');
    return;
    }

    if (classroom === "Krishna Seminar Hall") {
        if (!dept1Count) { showNotification('Krishna Seminar Hall requires at least one department!', 'error'); return; }

        const rows = 14, cols = 21;
        let classData = [[classroom], ["", ...Array.from({length: cols}, (_, i) => "C" + (i+1))]];
        let html = `<h3>Classroom ${classroom}</h3><table><tr>`;
        for (let c = 1; c <= cols; c++) html += `<th>C${c}</th>`;
        html += `</tr>`;

        const rolls = Array.from({length: dept1Count}, (_, i) => dept1RollFrom + i);

        // Columns to fill with students
        const studentCols = [1,4,5,8,11,14,17,18,21];

        function fillStudentColumns(rolls, studentCols, rows) {
            let result = {};
            studentCols.forEach(c => result[c] = []);
            let idx = 0;
            for (let r = 0; r < rows; r++) {
                for (let c of studentCols) {
                    if (idx < rolls.length) result[c].push(rolls[idx++]);
                    else result[c].push(undefined);
                }
            }
            return result;
        }

        const colRolls = fillStudentColumns(rolls, studentCols, rows);

        for (let r = 0; r < rows; r++) {
            html += '<tr>';
            let rowData = [];
            for (let c = 1; c <= cols; c++) {
                let cellContent = "-";
                if (colRolls[c] && colRolls[c][r] !== undefined) {
                    cellContent = `${dept1}<br>Roll:${colRolls[c][r]}`;
                }
                html += (cellContent === "-" ? `<td class="empty">-</td>` : `<td>${cellContent}</td>`);
                rowData.push(cellContent.replace(/<br>/g, " "));
            }
            html += '</tr>';
            classData.push(["R" + (r+1), ...rowData]);
        }

        document.getElementById('output').innerHTML += html;

        let downloadBtn = document.createElement('button');
        downloadBtn.textContent = 'Download Excel for ' + classroom;
        downloadBtn.style.background = 'linear-gradient(to right, #1a73e8, #155fc1)';
        downloadBtn.style.color = '#fff';
        downloadBtn.style.border = 'none';
        downloadBtn.style.borderRadius = '8px';
        downloadBtn.style.padding = '10px 18px';
        downloadBtn.style.fontWeight = '600';
        downloadBtn.style.cursor = 'pointer';
        downloadBtn.style.marginTop = '10px';
        downloadBtn.style.transition = 'all 0.3s ease';

        downloadBtn.addEventListener('mouseenter', () => {
            downloadBtn.style.background = 'linear-gradient(to right, #155fc1, #0d3f8a)';
        });
        downloadBtn.addEventListener('mouseleave', () => {
            downloadBtn.style.background = 'linear-gradient(to right, #1a73e8, #155fc1)';
        });

    downloadBtn.addEventListener('click', () => {
        let year = prompt("Enter year for roll number conversion (e.g., 3 for 3rd year):");
        if (!year) { showNotification('Year is required!', 'error'); return; }
        year = parseInt(year);

        // Format roll numbers
        function getFormattedRoll(roll, year,branch) {
            branch = branch.slice(0,3).toLowerCase();
            if (year === 3) {
                if(branch === 'cse'){
                if (roll <= 77) return '123CS' + String(roll).padStart(4, '0'); // 3rd-year CSE
                else return '523CS' + String(roll - 77).padStart(4, '0'); // Dual degree
                } 
                if (branch === 'aids'){
                    return '123AD' + String(roll).padStart(4, '0')
                }
                if(branch === 'ece'){
                    if (roll <= 44) return '123EC' + String(roll).padStart(4, '0'); // 3rd-year CSE
                    else return '523EC' + String(roll - 44).padStart(4, '0'); // Dual degree
                }
                if(branch === 'mech'){
                    if(roll <= 35) return '123ME' + String(roll).padStart(4, '0');
                    else return '523ME' + String(roll - 35).padStart(4, '0'); // Dual degree
                }
            }
            if (year === 2) {
                if(branch === 'cse'){
                if (roll <= 60) return '124CS' + String(roll).padStart(4, '0'); // 3rd-year CSE
                else return '524CS' + String(roll - 60).padStart(4, '0'); // Dual degree
                } 
                if (branch === 'aids'){
                    return '124AD' + String(roll).padStart(4, '0')
                }
                if(branch === 'ece'){
                    if (roll <= 60) return '124EC' + String(roll).padStart(4, '0'); // 3rd-year CSE
                    else return '524EC' + String(roll - 60).padStart(4, '0'); // Dual degree
                }
                if(branch === 'mech'){
                    if(roll <= 60) return '124ME' + String(roll).padStart(4, '0');
                    else return '524ME' + String(roll - 60).padStart(4, '0'); // Dual degree
                }
            }
            if (year === 1) {
                if(branch === 'cse'){
                if (roll <= 60) return '125CS' + String(roll).padStart(4, '0'); // 3rd-year CSE
                else return '525CS' + String(roll - 60).padStart(4, '0'); // Dual degree
                } 
                if (branch === 'aids'){
                    return '125AD' + String(roll).padStart(4, '0')
                }
                if(branch === 'ece'){
                    if (roll <= 60) return '125EC' + String(roll).padStart(4, '0'); // 3rd-year CSE
                    else return '525EC' + String(roll - 60).padStart(4, '0'); // Dual degree
                }
                if(branch === 'mech'){
                    if(roll <= 60) return '125ME' + String(roll).padStart(4, '0');
                    else return '525ME' + String(roll - 60).padStart(4, '0'); // Dual degree
                }
            }
            if (year === 4) {
                if(branch === 'cse'){
                if (roll <= 60) return '122CS' + String(roll).padStart(4, '0'); // 3rd-year CSE
                else return '522CS' + String(roll - 60).padStart(4, '0'); // Dual degree
                } 
                if (branch === 'aids'){
                    return '122AD' + String(roll).padStart(4, '0')
                }
                if(branch === 'ece'){
                    if (roll <= 60) return '122EC' + String(roll).padStart(4, '0'); // 3rd-year CSE
                    else return '522EC' + String(roll - 60).padStart(4, '0'); // Dual degree
                }
                if(branch === 'mech'){
                    if(roll <= 60) return '122ME' + String(roll).padStart(4, '0');
                    else return '522ME' + String(roll - 60).padStart(4, '0'); // Dual degree
                }
            }
            return roll; // fallback
            
        }

        // Deep copy classData
        const formattedData = JSON.parse(JSON.stringify(classData));

        // Replace roll numbers
        for (let r = 1; r < formattedData.length; r++) { // skip header row
        for (let c = 1; c < formattedData[r].length; c++) {
            let cell = formattedData[r][c];
            if (cell && cell.includes('Roll:')) {
                const parts = cell.split('Roll:');
                const deptName = parts[0].trim();
                let rollNum = parseInt(parts[1]);

                // Determine branch from deptName
                let branch = deptName.toLowerCase().slice(0, 3); // first 3 letters
                formattedData[r][c] = `${getFormattedRoll(rollNum, year, branch)}`;
            }
        }
    }


        // Export to Excel
        const wb = XLSX.utils.book_new();
        const ws = XLSX.utils.aoa_to_sheet(formattedData);
        XLSX.utils.book_append_sheet(wb, ws, classroom);
        XLSX.writeFile(wb, classroom + "_Seating.xlsx");
    });


        document.getElementById('output').appendChild(downloadBtn);
        selectedClassrooms.push(classroom);
        console.log('Selected classrooms:', selectedClassrooms);
        showNotification(`Classroom ${classroom} generated successfully!`, 'success');
        return;
    }


    // ---------- Special Classroom: Computing Lab ----------
    if (classroom === "Computing Lab") {
    if (!dept1Count || !dept2Count) { showNotification('Computing Lab requires two departments!', 'error'); return; }

    const rows = 6, cols = 16;
    const firstRowCols = 13; // Only 13 columns in the 1st row

    let classData = [[classroom], ["", ...Array.from({length: cols}, (_, i) => "C" + (i+1))]];
    let html = `<h3>Classroom ${classroom}</h3><table><tr>`;
    for (let c = 1; c <= cols; c++) html += `<th>C${c}</th>`;
    html += `</tr>`;

    const dept1Rolls = Array.from({length: dept1Count}, (_, i) => dept1RollFrom + i);
    const dept2Rolls = Array.from({length: dept2Count}, (_, i) => dept2RollFrom + i);

    const dept1Cols = [1, 3, 5, 7, 9, 11, 13, 15];
    const dept2Cols = [2, 4, 6, 8, 10, 12, 14, 16];

    // Merge both departments rolls with their respective columns
    function generateSeating(rolls, colsList) {
        let allSeats = [];
        let idx = 0;
        for (let r = 0; r < rows; r++) {
            for (let c of colsList) {
                if (!allSeats[r]) allSeats[r] = [];
                allSeats[r][c-1] = rolls[idx++] !== undefined ? rolls[idx-1] : undefined;
            }
        }
        return allSeats;
    }

    const combinedRolls = [];
    dept1Cols.forEach((col, i) => {
        for (let r = 0; r < rows; r++) {
            const roll = dept1Rolls[i*rows + r];
            if (roll !== undefined) combinedRolls.push({roll, col, dept: dept1});
        }
    });
    dept2Cols.forEach((col, i) => {
        for (let r = 0; r < rows; r++) {
            const roll = dept2Rolls[i*rows + r];
            if (roll !== undefined) combinedRolls.push({roll, col, dept: dept2});
        }
    });

    // Fill table row by row
    // Fill table row by row
    const seating = Array.from({length: rows}, () => Array(cols).fill("-"));

    combinedRolls.forEach(student => {
        let placed = false;
        let row = 0;
        while (row < rows && !placed) {
            // Skip first row for columns > 13
            if (row === 0 && student.col > firstRowCols) {
                row = 1; // ✅ move directly to 2nd row (instead of skipping student)
            }

            if (seating[row][student.col - 1] === "-") {
                seating[row][student.col - 1] = `${student.dept}<br>Roll:${student.roll}`;
                placed = true;
            } else {
                row++;
            }
        }
    });

    // ---------- MANUAL FIX FOR 16th COLUMN ECE 41-46 ----------
    if (classroom === "Computing Lab") {
    // Existing placement logic runs first
    // ...

    // ---------- Manual fix for 16th column if dept2 exceeds 41 ----------
        if (dept2Count > 41) {
            const col16 = 15; // 16th column (0-indexed)
            const eceStartRoll = dept2RollFrom + 41; // roll number 42 (0-based offset)
            const eceDept = dept2; // department 2 name

            // Fill remaining 5 rows from R2 to R6
            for (let r = 1; r <= 5; r++) { // r+1 = R2..R6
                // Only overwrite if there is a corresponding roll
                const rollNum = eceStartRoll + r - 1;
                if (rollNum <= dept2RollFrom + dept2Count - 1) {
                    seating[r][col16] = `${eceDept}<br>Roll:${rollNum}`;
                }
            }
        }
    }



    for (let r = 0; r < rows; r++) {
        html += "<tr>";
        for (let c = 0; c < cols; c++) {
            const cell = seating[r][c];
            html += cell === "-" ? `<td class="empty">-</td>` : `<td>${cell}</td>`;
        }
        html += "</tr>";
        classData.push(["R" + (r+1), ...seating[r].map(s => s === "-" ? "-" : s.replace(/<br>/g, " "))]);
    }

    document.getElementById('output').innerHTML += html;

    // Add download button (keep your existing download code)
    let downloadBtn = document.createElement('button');
    downloadBtn.textContent = 'Download Excel for ' + classroom;
    downloadBtn.style.background = 'linear-gradient(to right, #1a73e8, #155fc1)';
    downloadBtn.style.color = '#fff';
    downloadBtn.style.border = 'none';
    downloadBtn.style.borderRadius = '8px';
    downloadBtn.style.padding = '10px 18px';
    downloadBtn.style.fontWeight = '600';
    downloadBtn.style.cursor = 'pointer';
    downloadBtn.style.marginTop = '10px';
    downloadBtn.style.transition = 'all 0.3s ease';

    downloadBtn.addEventListener('mouseenter', () => {
        downloadBtn.style.background = 'linear-gradient(to right, #155fc1, #0d3f8a)';
    });
    downloadBtn.addEventListener('mouseleave', () => {
        downloadBtn.style.background = 'linear-gradient(to right, #1a73e8, #155fc1)';
    });

    downloadBtn.addEventListener('click', () => {
        let year = prompt("Enter year for roll number conversion (e.g., 3 for 3rd year):");
        if (!year) { showNotification('Year is required!', 'error'); return; }
        year = parseInt(year);

        function getFormattedRoll(roll, year,branch) {
            branch = branch.slice(0,3).toLowerCase();
            if (year === 3) {
                if(branch === 'cse'){
                if (roll <= 77) return '123CS' + String(roll).padStart(4, '0'); // 3rd-year CSE
                else return '523CS' + String(roll - 77).padStart(4, '0'); // Dual degree
                } 
                if (branch === 'aids'){
                    return '123AD' + String(roll).padStart(4, '0')
                }
                if(branch === 'ece'){
                    if (roll <= 44) return '123EC' + String(roll).padStart(4, '0'); // 3rd-year CSE
                    else return '523EC' + String(roll - 44).padStart(4, '0'); // Dual degree
                }
                if(branch === 'mech'){
                    if(roll <= 35) return '123ME' + String(roll).padStart(4, '0');
                    else return '523ME' + String(roll - 35).padStart(4, '0'); // Dual degree
                }
            }
            if (year === 2) {
                if(branch === 'cse'){
                if (roll <= 60) return '124CS' + String(roll).padStart(4, '0'); // 3rd-year CSE
                else return '524CS' + String(roll - 60).padStart(4, '0'); // Dual degree
                } 
                if (branch === 'aids'){
                    return '124AD' + String(roll).padStart(4, '0')
                }
                if(branch === 'ece'){
                    if (roll <= 60) return '124EC' + String(roll).padStart(4, '0'); // 3rd-year CSE
                    else return '524EC' + String(roll - 60).padStart(4, '0'); // Dual degree
                }
                if(branch === 'mech'){
                    if(roll <= 60) return '124ME' + String(roll).padStart(4, '0');
                    else return '524ME' + String(roll - 60).padStart(4, '0'); // Dual degree
                }
            }
            if (year === 1) {
                if(branch === 'cse'){
                if (roll <= 60) return '125CS' + String(roll).padStart(4, '0'); // 3rd-year CSE
                else return '525CS' + String(roll - 60).padStart(4, '0'); // Dual degree
                } 
                if (branch === 'aids'){
                    return '125AD' + String(roll).padStart(4, '0')
                }
                if(branch === 'ece'){
                    if (roll <= 60) return '125EC' + String(roll).padStart(4, '0'); // 3rd-year CSE
                    else return '525EC' + String(roll - 60).padStart(4, '0'); // Dual degree
                }
                if(branch === 'mech'){
                    if(roll <= 60) return '125ME' + String(roll).padStart(4, '0');
                    else return '525ME' + String(roll - 60).padStart(4, '0'); // Dual degree
                }
            }
            if (year === 4) {
                if(branch === 'cse'){
                if (roll <= 60) return '122CS' + String(roll).padStart(4, '0'); // 3rd-year CSE
                else return '522CS' + String(roll - 60).padStart(4, '0'); // Dual degree
                } 
                if (branch === 'aids'){
                    return '122AD' + String(roll).padStart(4, '0')
                }
                if(branch === 'ece'){
                    if (roll <= 60) return '122EC' + String(roll).padStart(4, '0'); // 3rd-year CSE
                    else return '522EC' + String(roll - 60).padStart(4, '0'); // Dual degree
                }
                if(branch === 'mech'){
                    if(roll <= 60) return '122ME' + String(roll).padStart(4, '0');
                    else return '522ME' + String(roll - 60).padStart(4, '0'); // Dual degree
                }
            }
            return roll; // fallback
            
        }

        // Deep copy classData
        const formattedData = JSON.parse(JSON.stringify(classData));

        // Replace roll numbers
        for (let r = 1; r < formattedData.length; r++) { // skip header row
        for (let c = 1; c < formattedData[r].length; c++) {
            let cell = formattedData[r][c];
            if (cell && cell.includes('Roll:')) {
                const parts = cell.split('Roll:');
                const deptName = parts[0].trim();
                let rollNum = parseInt(parts[1]);

                // Determine branch from deptName
                let branch = deptName.toLowerCase().slice(0, 3); // first 3 letters
                formattedData[r][c] = `${getFormattedRoll(rollNum, year, branch)}`;
            }
        }
    }


        // Export to Excel
        const wb = XLSX.utils.book_new();
        const ws = XLSX.utils.aoa_to_sheet(formattedData);
        XLSX.utils.book_append_sheet(wb, ws, classroom);
        XLSX.writeFile(wb, classroom + "_Seating.xlsx");
    });

    document.getElementById('output').appendChild(downloadBtn);
    selectedClassrooms.push(classroom);
    console.log('Selected classrooms:', selectedClassrooms);
    showNotification(`Classroom ${classroom} generated successfully!`, 'success');
    return;
}



    // ---------- Existing Classroom Logic ----------
   if (!dept1Count && !dept2Count && !dept3Count) {
        showNotification('At least one department must have students!', 'error');
        return;
    }
    if (dept1Count + dept2Count + dept3Count > 60) {
        showNotification('Total students cannot exceed 60!', 'error');
        return;
    }

    const rows = 10, cols = 9;
    let classData = [[classroom], ["", ...Array.from({ length: cols }, (_, i) => "C" + (i + 1))]];
    let html = `<h3>Classroom ${classroom}</h3><table><tr>`;
    for (let c = 1; c <= cols; c++) html += `<th>C${c}</th>`;
    html += `</tr>`;

    // Generate rolls for each department
    const dept1Rolls = Array.from({ length: dept1Count }, (_, i) => dept1RollFrom + i);
    const dept2Rolls = Array.from({ length: dept2Count }, (_, i) => dept2RollFrom + i);
    const dept3Rolls = Array.from({ length: dept3Count }, (_, i) => dept3RollFrom + i);

    // ✅ Column layout
    let dept1Cols = [1, 4, 7];
    let dept2Cols = [3, 6, 9];

    // ✅ Special case: only one department present → use [2,5,8]
    const activeDepts = [dept1Count > 0, dept2Count > 0, dept3Count > 0].filter(Boolean).length;
    if (activeDepts === 1) {
        if (dept1Count) dept1Cols = [2, 5, 8];
        if (dept2Count) dept2Cols = [2, 5, 8];
        if (dept3Count) dept2Cols = [2, 5, 8]; // dept3 uses same structure as dept2
    }

    // ---- Fill columns helper (keeps department info) ----
    function fillCols(rolls, colsList, deptName) {
        let result = {};
        let idx = 0;
        for (let c of colsList) {
            result[c] = [];
            for (let r = 0; r < rows && idx < rolls.length; r++, idx++) {
                result[c].push({ roll: rolls[idx], dept: deptName });
            }
        }
        return result;
    }

    // ---- Fill Dept 1 and Dept 2 normally ----
    let dept1ColRolls = fillCols(dept1Rolls, dept1Cols, dept1);
    let dept2ColRolls = fillCols(dept2Rolls, dept2Cols, dept2);

    // ---- Continue Dept 3 in the same Dept 2 columns ----
    if (dept3Rolls.length > 0) {
        let idx = 0;
        for (let c of dept2Cols) {
            if (!dept2ColRolls[c]) dept2ColRolls[c] = [];
            while (dept2ColRolls[c].length < rows && idx < dept3Rolls.length) {
                dept2ColRolls[c].push({ roll: dept3Rolls[idx++], dept: dept3 });
            }
        }
    }

    // ---- Rendering ----
    for (let r = 0; r < rows; r++) {
        html += '<tr>';
        let rowData = [];
        for (let c = 1; c <= cols; c++) {
            let cellContent = "-";

            if (dept1Cols.includes(c) && dept1ColRolls[c]?.[r]) {
                const { roll, dept } = dept1ColRolls[c][r];
                cellContent = `${dept}<br>Roll:${roll}`;
            } 
            else if (dept2Cols.includes(c) && dept2ColRolls[c]?.[r]) {
                const { roll, dept } = dept2ColRolls[c][r];
                cellContent = `${dept}<br>Roll:${roll}`;
            }

            html += (cellContent === "-" ? `<td class="empty">-</td>` : `<td>${cellContent}</td>`);
            rowData.push(cellContent.replace(/<br>/g, " "));
        }
        html += '</tr>';
        classData.push(["R" + (r + 1), ...rowData]);
    }

    document.getElementById('output').innerHTML += html;


    let downloadBtn = document.createElement('button');
    downloadBtn.textContent = 'Download Excel for ' + classroom;
    downloadBtn.style.background = 'linear-gradient(to right, #1a73e8, #155fc1)';
    downloadBtn.style.color = '#fff';
    downloadBtn.style.border = 'none';
    downloadBtn.style.borderRadius = '8px';
    downloadBtn.style.padding = '10px 18px';
    downloadBtn.style.fontWeight = '600';
    downloadBtn.style.cursor = 'pointer';
    downloadBtn.style.marginTop = '10px';
    downloadBtn.style.transition = 'all 0.3s ease';

    downloadBtn.addEventListener('mouseenter', () => {
        downloadBtn.style.background = 'linear-gradient(to right, #155fc1, #0d3f8a)';
    });
    downloadBtn.addEventListener('mouseleave', () => {
        downloadBtn.style.background = 'linear-gradient(to right, #1a73e8, #155fc1)';
    });

    downloadBtn.addEventListener('click', () => {
        let year = prompt("Enter year for roll number conversion (e.g., 3 for 3rd year):");
        if (!year) { showNotification('Year is required!', 'error'); return; }
        year = parseInt(year);

        // Format roll numbers
        function getFormattedRoll(roll, year,branch) {
            branch = branch.slice(0,3).toLowerCase();
            if (year === 3) {
                if(branch === 'cse'){
                if (roll <= 77) return '123CS' + String(roll).padStart(4, '0'); // 3rd-year CSE
                else return '523CS' + String(roll - 77).padStart(4, '0'); // Dual degree
                } 
                if (branch === 'aids'){
                    return '123AD' + String(roll).padStart(4, '0')
                }
                if(branch === 'ece'){
                    if (roll <= 44) return '123EC' + String(roll).padStart(4, '0'); // 3rd-year CSE
                    else return '523EC' + String(roll - 44).padStart(4, '0'); // Dual degree
                }
                if(branch === 'mech'){
                    if(roll <= 35) return '123ME' + String(roll).padStart(4, '0');
                    else return '523ME' + String(roll - 35).padStart(4, '0'); // Dual degree
                }
            }
            if (year === 2) {
                if(branch === 'cse'){
                if (roll <= 60) return '124CS' + String(roll).padStart(4, '0'); // 3rd-year CSE
                else return '524CS' + String(roll - 60).padStart(4, '0'); // Dual degree
                } 
                if (branch === 'aids'){
                    return '124AD' + String(roll).padStart(4, '0')
                }
                if(branch === 'ece'){
                    if (roll <= 60) return '124EC' + String(roll).padStart(4, '0'); // 3rd-year CSE
                    else return '524EC' + String(roll - 60).padStart(4, '0'); // Dual degree
                }
                if(branch === 'mech'){
                    if(roll <= 60) return '124ME' + String(roll).padStart(4, '0');
                    else return '524ME' + String(roll - 60).padStart(4, '0'); // Dual degree
                }
            }
            if (year === 1) {
                if(branch === 'cse'){
                if (roll <= 60) return '125CS' + String(roll).padStart(4, '0'); // 3rd-year CSE
                else return '525CS' + String(roll - 60).padStart(4, '0'); // Dual degree
                } 
                if (branch === 'aids'){
                    return '125AD' + String(roll).padStart(4, '0')
                }
                if(branch === 'ece'){
                    if (roll <= 60) return '125EC' + String(roll).padStart(4, '0'); // 3rd-year CSE
                    else return '525EC' + String(roll - 60).padStart(4, '0'); // Dual degree
                }
                if(branch === 'mech'){
                    if(roll <= 60) return '125ME' + String(roll).padStart(4, '0');
                    else return '525ME' + String(roll - 60).padStart(4, '0'); // Dual degree
                }
            }
            if (year === 4) {
                if(branch === 'cse'){
                if (roll <= 60) return '122CS' + String(roll).padStart(4, '0'); // 3rd-year CSE
                else return '522CS' + String(roll - 60).padStart(4, '0'); // Dual degree
                } 
                if (branch === 'aids'){
                    return '122AD' + String(roll).padStart(4, '0')
                }
                if(branch === 'ece'){
                    if (roll <= 60) return '122EC' + String(roll).padStart(4, '0'); // 3rd-year CSE
                    else return '522EC' + String(roll - 60).padStart(4, '0'); // Dual degree
                }
                if(branch === 'mech'){
                    if(roll <= 60) return '122ME' + String(roll).padStart(4, '0');
                    else return '522ME' + String(roll - 60).padStart(4, '0'); // Dual degree
                }
            }
            return roll; // fallback
            
        }

        // Deep copy classData
        const formattedData = JSON.parse(JSON.stringify(classData));

        // Replace roll numbers
        for (let r = 1; r < formattedData.length; r++) { // skip header row
        for (let c = 1; c < formattedData[r].length; c++) {
            let cell = formattedData[r][c];
            if (cell && cell.includes('Roll:')) {
                const parts = cell.split('Roll:');
                const deptName = parts[0].trim();
                let rollNum = parseInt(parts[1]);

                // Determine branch from deptName
                let branch = deptName.toLowerCase().slice(0, 3); // first 3 letters
                formattedData[r][c] = `${getFormattedRoll(rollNum, year, branch)}`;
            }
        }
    }


        // Export to Excel
        const wb = XLSX.utils.book_new();
        const ws = XLSX.utils.aoa_to_sheet(formattedData);
        XLSX.utils.book_append_sheet(wb, ws, classroom);
        XLSX.writeFile(wb, classroom + "_Seating.xlsx");
    });


    document.getElementById('output').appendChild(downloadBtn);
    selectedClassrooms.push(classroom);
    console.log('Selected classrooms:', selectedClassrooms);
    showNotification(`Classroom ${classroom} generated successfully!`, 'success');

});
document.getElementById('cancelBtn').addEventListener('click', () => {
    document.getElementById('output').innerHTML = '';
    selectedClassrooms = [];
    showNotification('All classroom arrangements cancelled!', 'success');
});
</script>
</body>
</html>
