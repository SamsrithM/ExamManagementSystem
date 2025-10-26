<?php
// view_slots.php

// Database connection using environment variables
$db_host = getenv('DB_HOST') ?: '127.0.0.1';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') ?: '';
$db_name = getenv('DB_ROOM') ?: 'room_allocation';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("<h3>Database connection failed: " . $conn->connect_error . "</h3>");
}

// Handle deletion
$delete_message = '';
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']); // sanitize input
    if ($conn->query("DELETE FROM faculty_slot_selection WHERE slot_id = $delete_id")) {
        $delete_message = "Slot ID $delete_id deleted successfully!";
    } else {
        $delete_message = "Error deleting Slot ID $delete_id!";
    }
}

// Fetch faculty slot selection data
$sql = "SELECT faculty_email, slot_id, slot_date, slot_time, selected_at 
        FROM faculty_slot_selection 
        ORDER BY slot_date, slot_time";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>👀 View Faculty Exam Slots</title>
<style>
  body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #f9fafb;
    margin: 0;
    padding: 0;
  }

  h2 {
    text-align: center;
    margin-top: 25px;
    color: #333;
    font-size: 26px;
  }

  table {
    border-collapse: collapse;
    margin: 40px auto;
    width: 95%;
    max-width: 1000px;
    background-color: #ffffff;
    box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
    border-radius: 10px;
    overflow: hidden;
  }

  th,
  td {
    padding: 12px 18px;
    text-align: center;
    border-bottom: 1px solid #e2e8f0;
    font-size: 15px;
  }

  th {
    background-color: #2563eb;
    color: white;
    text-transform: uppercase;
    font-size: 14px;
    letter-spacing: 0.5px;
  }

  tr:hover {
    background-color: #f1f5f9;
  }

  .delete-btn {
    background-color: #ef4444;
    color: #fff;
    padding: 6px 12px;
    border-radius: 5px;
    text-decoration: none;
    font-weight: bold;
    transition: 0.2s;
    border: none;
    cursor: pointer;
  }

  .delete-btn:hover {
    background-color: #b91c1c;
  }

  .no-data {
    text-align: center;
    color: #555;
    font-size: 16px;
    margin-top: 30px;
  }

  .back-btn-container {
    text-align: center;
    margin: 40px 0;
  }

  .back-btn {
    background-color: #2563eb;
    color: #ffffff;
    text-decoration: none;
    padding: 10px 20px;
    border-radius: 6px;
    font-weight: bold;
    font-size: 15px;
    transition: 0.2s;
  }

  .back-btn:hover {
    background-color: #1e40af;
  }

  /* Modal Styles */
  #deleteModal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    justify-content: center;
    align-items: center;
    z-index: 1000;
  }

  #deleteModalContent {
    background: white;
    padding: 25px 30px;
    border-radius: 8px;
    max-width: 400px;
    width: 90%;
    text-align: center;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
  }

  #deleteModalContent p {
    margin-bottom: 20px;
    font-size: 16px;
    color: #333;
  }

  #deleteModalContent button {
    padding: 8px 18px;
    margin: 0 10px;
    border: none;
    border-radius: 6px;
    font-weight: bold;
    cursor: pointer;
    transition: 0.2s;
  }

  #confirmDelete {
    background-color: #ef4444;
    color: #fff;
  }

  #cancelDelete {
    background-color: #6b7280;
    color: #fff;
  }

  #confirmDelete:hover {
    background-color: #b91c1c;
  }

  #cancelDelete:hover {
    background-color: #4b5563;
  }

  /* Responsive Styles */
  @media screen and (max-width: 768px) {
    h2 {
      font-size: 22px;
    }

    table,
    thead,
    tbody,
    th,
    td,
    tr {
      display: block;
    }

    thead {
      display: none;
    }

    tr {
      margin-bottom: 15px;
      border: 1px solid #ccc;
      border-radius: 8px;
      padding: 10px;
      background: #fff;
    }

    td {
      text-align: left;
      padding: 8px 10px;
      position: relative;
    }

    td::before {
      content: attr(data-label);
      font-weight: bold;
      color: #2563eb;
      display: block;
      margin-bottom: 5px;
    }

    .delete-btn {
      width: 100%;
      text-align: center;
    }
  }

  @media screen and (max-width: 480px) {
    .back-btn {
      font-size: 14px;
      padding: 8px 16px;
    }

    #deleteModalContent p {
      font-size: 14px;
    }

    #deleteModalContent button {
      font-size: 14px;
      padding: 6px 14px;
    }
  }
</style>
</head>
<body>

<h2>👀 View Faculty Exam Slots</h2>

<?php
if ($result && $result->num_rows > 0) {
    echo "<table>";
    echo "<tr>
            <th>Faculty Email</th>
            <th>Slot ID</th>
            <th>Slot Date</th>
            <th>Slot Time</th>
            <th>Selected At</th>
            <th>Action</th>
          </tr>";

    while ($row = $result->fetch_assoc()) {
        $slot_id = $row['slot_id'];
        echo "<tr>
                <td>{$row['faculty_email']}</td>
                <td>{$row['slot_id']}</td>
                <td>{$row['slot_date']}</td>
                <td>{$row['slot_time']}</td>
                <td>{$row['selected_at']}</td>
                <td><button class='delete-btn' data-id='$slot_id'>Delete</button></td>
              </tr>";
    }

    echo "</table>";
} else {
    echo "<p class='no-data'>No slot selections found!</p>";
}
$conn->close();
?>

<div class="back-btn-container">
    <a href="admin_front_page.php" class="back-btn">⬅️ Back to Dashboard</a>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal">
  <div id="deleteModalContent">
    <p>Are you sure you want to delete this slot?</p>
    <button id="confirmDelete">Yes, Delete</button>
    <button id="cancelDelete">Cancel</button>
  </div>
</div>

<script>
let deleteId = null;
const modal = document.getElementById('deleteModal');
const confirmBtn = document.getElementById('confirmDelete');
const cancelBtn = document.getElementById('cancelDelete');

document.querySelectorAll('.delete-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        deleteId = btn.getAttribute('data-id');
        modal.style.display = 'flex';
    });
});

confirmBtn.addEventListener('click', () => {
    if(deleteId) {
        window.location.href = '?delete_id=' + deleteId;
    }
});

cancelBtn.addEventListener('click', () => {
    modal.style.display = 'none';
    deleteId = null;
});

window.addEventListener('click', e => {
    if(e.target === modal) {
        modal.style.display = 'none';
        deleteId = null;
    }
});
</script>

</body>
</html>
