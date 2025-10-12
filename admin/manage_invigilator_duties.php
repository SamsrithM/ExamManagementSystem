<?php
/**
 * Admin Interface for Managing Invigilator Duties
 */

session_start();

// Simple authentication check (in production, use proper authentication)
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit();
}

require_once '../config/database.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Invigilator Duties - Admin Panel</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            background-color: #f4f7f9;
            color: #2c3e50;
        }

        .header {
            background: linear-gradient(135deg, #1abc9c 0%, #16a085 100%);
            color: white;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }

        .nav-tabs {
            display: flex;
            background: white;
            border-radius: 8px 8px 0 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 0;
        }

        .nav-tab {
            padding: 15px 25px;
            cursor: pointer;
            border: none;
            background: none;
            font-size: 16px;
            font-weight: 500;
            color: #666;
            transition: all 0.3s ease;
        }

        .nav-tab.active {
            background: #1abc9c;
            color: white;
        }

        .nav-tab:hover {
            background: #f8f9fa;
        }

        .nav-tab.active:hover {
            background: #16a085;
        }

        .tab-content {
            background: white;
            padding: 30px;
            border-radius: 0 0 8px 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            min-height: 400px;
        }

        .tab-pane {
            display: none;
        }

        .tab-pane.active {
            display: block;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #2c3e50;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            box-sizing: border-box;
        }

        .form-group textarea {
            height: 80px;
            resize: vertical;
        }

        .form-row {
            display: flex;
            gap: 20px;
        }

        .form-row .form-group {
            flex: 1;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: background-color 0.3s ease;
        }

        .btn-primary {
            background-color: #1abc9c;
            color: white;
        }

        .btn-primary:hover {
            background-color: #16a085;
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
        }

        .btn-danger {
            background-color: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background-color: #c82333;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .table th,
        .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
        }

        .table tr:hover {
            background-color: #f8f9fa;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-assigned {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-confirmed {
            background-color: #d1ecf1;
            color: #0c5460;
        }

        .status-present {
            background-color: #d4edda;
            color: #155724;
        }

        .status-absent {
            background-color: #f8d7da;
            color: #721c24;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }

        .alert-success {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }

        .alert-danger {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }

        .loading {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .logout-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            background-color: rgba(255,255,255,0.2);
            color: white;
            border: 1px solid rgba(255,255,255,0.3);
            padding: 8px 16px;
            border-radius: 4px;
            text-decoration: none;
            transition: background-color 0.3s ease;
        }

        .logout-btn:hover {
            background-color: rgba(255,255,255,0.3);
            color: white;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Invigilator Duty Management</h1>
        <p>Admin Panel - Manage Exams and Invigilator Assignments</p>
        <a href="admin_logout.php" class="logout-btn">Logout</a>
    </div>

    <div class="container">
        <div class="nav-tabs">
            <button class="nav-tab active" onclick="showTab('exams')">Manage Exams</button>
            <button class="nav-tab" onclick="showTab('duties')">Assign Duties</button>
            <button class="nav-tab" onclick="showTab('reports')">Reports</button>
        </div>

        <div class="tab-content">
            <!-- Manage Exams Tab -->
            <div id="exams" class="tab-pane active">
                <h2>Manage Exams</h2>
                <button class="btn btn-primary" onclick="showExamForm()">Add New Exam</button>
                
                <div id="examForm" style="display: none; margin-top: 20px; padding: 20px; background: #f8f9fa; border-radius: 4px;">
                    <h3>Add/Edit Exam</h3>
                    <form id="examFormData">
                        <input type="hidden" id="examId" name="exam_id">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="examTitle">Exam Title</label>
                                <input type="text" id="examTitle" name="title" required>
                            </div>
                            <div class="form-group">
                                <label for="examDate">Exam Date</label>
                                <input type="date" id="examDate" name="exam_date" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="startTime">Start Time</label>
                                <input type="time" id="startTime" name="start_time" required>
                            </div>
                            <div class="form-group">
                                <label for="endTime">End Time</label>
                                <input type="time" id="endTime" name="end_time" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="venue">Venue</label>
                                <input type="text" id="venue" name="venue" required>
                            </div>
                            <div class="form-group">
                                <label for="subjectCode">Subject Code</label>
                                <input type="text" id="subjectCode" name="subject_code">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="subjectName">Subject Name</label>
                                <input type="text" id="subjectName" name="subject_name">
                            </div>
                            <div class="form-group">
                                <label for="totalStudents">Total Students</label>
                                <input type="number" id="totalStudents" name="total_students" min="0">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Save Exam</button>
                        <button type="button" class="btn btn-secondary" onclick="hideExamForm()">Cancel</button>
                    </form>
                </div>

                <div id="examsList">
                    <div class="loading">Loading exams...</div>
                </div>
            </div>

            <!-- Assign Duties Tab -->
            <div id="duties" class="tab-pane">
                <h2>Assign Invigilator Duties</h2>
                <div class="form-row">
                    <div class="form-group">
                        <label for="dutyExam">Select Exam</label>
                        <select id="dutyExam" onchange="loadExamDetails()">
                            <option value="">Select an exam...</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="dutyFaculty">Select Faculty</label>
                        <select id="dutyFaculty">
                            <option value="">Select faculty...</option>
                        </select>
                    </div>
                </div>
                <button class="btn btn-primary" onclick="assignDuty()">Assign Duty</button>
                
                <div id="dutiesList">
                    <div class="loading">Loading duties...</div>
                </div>
            </div>

            <!-- Reports Tab -->
            <div id="reports" class="tab-pane">
                <h2>Reports</h2>
                <div class="form-row">
                    <div class="form-group">
                        <label for="reportFromDate">From Date</label>
                        <input type="date" id="reportFromDate">
                    </div>
                    <div class="form-group">
                        <label for="reportToDate">To Date</label>
                        <input type="date" id="reportToDate">
                    </div>
                    <div class="form-group">
                        <label for="reportFaculty">Faculty</label>
                        <select id="reportFaculty">
                            <option value="">All Faculty</option>
                        </select>
                    </div>
                </div>
                <button class="btn btn-primary" onclick="generateReport()">Generate Report</button>
                
                <div id="reportResults">
                    <!-- Report results will be displayed here -->
                </div>
            </div>
        </div>
    </div>

    <script>
        // Tab switching functionality
        function showTab(tabName) {
            // Hide all tab panes
            const tabPanes = document.querySelectorAll('.tab-pane');
            tabPanes.forEach(pane => pane.classList.remove('active'));
            
            // Remove active class from all tabs
            const tabs = document.querySelectorAll('.nav-tab');
            tabs.forEach(tab => tab.classList.remove('active'));
            
            // Show selected tab pane
            document.getElementById(tabName).classList.add('active');
            
            // Add active class to clicked tab
            event.target.classList.add('active');
            
            // Load data for the selected tab
            switch(tabName) {
                case 'exams':
                    loadExams();
                    break;
                case 'duties':
                    loadDuties();
                    loadFacultyList();
                    break;
                case 'reports':
                    loadFacultyList('reportFaculty');
                    break;
            }
        }

        // Load exams list
        async function loadExams() {
            try {
                const response = await fetch('../api/exams.php');
                const result = await response.json();
                
                if (result.success) {
                    displayExams(result.data);
                } else {
                    document.getElementById('examsList').innerHTML = 
                        '<div class="alert alert-danger">Error loading exams: ' + result.error + '</div>';
                }
            } catch (error) {
                document.getElementById('examsList').innerHTML = 
                    '<div class="alert alert-danger">Network error: ' + error.message + '</div>';
            }
        }

        // Display exams in table
        function displayExams(exams) {
            const container = document.getElementById('examsList');
            
            if (exams.length === 0) {
                container.innerHTML = '<div class="alert alert-info">No exams found.</div>';
                return;
            }

            let html = `
                <table class="table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Venue</th>
                            <th>Subject</th>
                            <th>Students</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            exams.forEach(exam => {
                html += `
                    <tr>
                        <td>${exam.title}</td>
                        <td>${exam.exam_date}</td>
                        <td>${exam.start_time} - ${exam.end_time}</td>
                        <td>${exam.venue}</td>
                        <td>${exam.subject_code || ''} ${exam.subject_name || ''}</td>
                        <td>${exam.total_students || 0}</td>
                        <td><span class="status-badge status-${exam.status}">${exam.status}</span></td>
                        <td>
                            <button class="btn btn-secondary" onclick="editExam(${exam.id})">Edit</button>
                            <button class="btn btn-danger" onclick="deleteExam(${exam.id})">Delete</button>
                        </td>
                    </tr>
                `;
            });

            html += '</tbody></table>';
            container.innerHTML = html;
        }

        // Show/hide exam form
        function showExamForm() {
            document.getElementById('examForm').style.display = 'block';
            document.getElementById('examFormData').reset();
            document.getElementById('examId').value = '';
        }

        function hideExamForm() {
            document.getElementById('examForm').style.display = 'none';
        }

        // Load faculty list
        async function loadFacultyList(selectId = 'dutyFaculty') {
            try {
                const response = await fetch('../api/faculty_auth.php?action=list');
                const result = await response.json();
                
                if (result.success) {
                    const select = document.getElementById(selectId);
                    select.innerHTML = '<option value="">Select faculty...</option>';
                    
                    result.data.forEach(faculty => {
                        const option = document.createElement('option');
                        option.value = faculty.id;
                        option.textContent = `${faculty.first_name} ${faculty.last_name} (${faculty.department})`;
                        select.appendChild(option);
                    });
                }
            } catch (error) {
                console.error('Error loading faculty:', error);
            }
        }

        // Load duties list
        async function loadDuties() {
            try {
                const response = await fetch('../api/invigilator_duties.php');
                const result = await response.json();
                
                if (result.success) {
                    displayDuties(result.data);
                } else {
                    document.getElementById('dutiesList').innerHTML = 
                        '<div class="alert alert-danger">Error loading duties: ' + result.error + '</div>';
                }
            } catch (error) {
                document.getElementById('dutiesList').innerHTML = 
                    '<div class="alert alert-danger">Network error: ' + error.message + '</div>';
            }
        }

        // Display duties in table
        function displayDuties(duties) {
            const container = document.getElementById('dutiesList');
            
            if (duties.length === 0) {
                container.innerHTML = '<div class="alert alert-info">No duties assigned.</div>';
                return;
            }

            let html = `
                <table class="table">
                    <thead>
                        <tr>
                            <th>Exam</th>
                            <th>Date</th>
                            <th>Faculty</th>
                            <th>Venue</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            duties.forEach(duty => {
                html += `
                    <tr>
                        <td>${duty.title}</td>
                        <td>${duty.duty_date}</td>
                        <td>${duty.faculty_name}</td>
                        <td>${duty.venue}</td>
                        <td><span class="status-badge status-${duty.status}">${duty.status}</span></td>
                        <td>
                            <button class="btn btn-danger" onclick="removeDuty(${duty.id})">Remove</button>
                        </td>
                    </tr>
                `;
            });

            html += '</tbody></table>';
            container.innerHTML = html;
        }

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            loadExams();
            loadFacultyList();
        });
    </script>
</body>
</html>
