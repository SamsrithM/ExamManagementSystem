// Global variables to store duties data
let upcomingDuties = [];
let pastDuties = [];
let currentFacultyId = 1; // Default faculty ID - should be set from session

// API Configuration
const API_BASE_URL = '../api/';

// API Functions
async function fetchUpcomingDuties() {
  try {
    const response = await fetch(`${API_BASE_URL}invigilator_duties.php?type=upcoming&faculty_id=${currentFacultyId}`);
    const result = await response.json();
    
    if (result.success) {
      upcomingDuties = result.data;
      return upcomingDuties;
    } else {
      console.error('Error fetching upcoming duties:', result.error);
      return [];
    }
  } catch (error) {
    console.error('Network error:', error);
    return [];
  }
}

async function fetchPastDuties() {
  try {
    const response = await fetch(`${API_BASE_URL}invigilator_duties.php?type=past&faculty_id=${currentFacultyId}`);
    const result = await response.json();
    
    if (result.success) {
      pastDuties = result.data;
      return pastDuties;
    } else {
      console.error('Error fetching past duties:', result.error);
      return [];
    }
  } catch (error) {
    console.error('Network error:', error);
    return [];
  }
}

async function updateDutyStatus(dutyId, status, notes = '') {
  try {
    const response = await fetch(`${API_BASE_URL}invigilator_duties.php`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        duty_id: dutyId,
        status: status,
        notes: notes
      })
    });
    
    const result = await response.json();
    return result;
  } catch (error) {
    console.error('Network error:', error);
    return { success: false, error: 'Network error' };
  }
}

function isWithinNext7Days(dateStr) {
  const today = new Date();
  const targetDate = new Date(dateStr);
  const diffTime = targetDate - today;
  const diffDays = diffTime / (1000 * 60 * 60 * 24);
  return diffDays >= 0 && diffDays <= 7;
}

function renderDuties(duties, containerId) {
  const container = document.getElementById(containerId);
  container.innerHTML = '';

  if (duties.length === 0) {
    container.innerHTML = '<li class="no-duties">No duties found</li>';
    return;
  }

  duties.forEach((duty, index) => {
    const li = document.createElement('li');
    li.className = 'duty-item';

    const detailsDiv = document.createElement('div');
    detailsDiv.className = 'duty-details';

    const title = document.createElement('div');
    title.className = 'duty-title';
    title.textContent = duty.title;

    const meta = document.createElement('div');
    meta.className = 'duty-meta';
    meta.innerHTML = `
      📅 ${new Date(duty.duty_date).toLocaleDateString()} &nbsp; 🕒 ${duty.start_time} - ${duty.end_time} &nbsp; 📍 ${duty.venue}
    `;

    const badge = document.createElement('div');
    if (duty.status === 'present') badge.className = 'status-badge status-present';
    else if (duty.status === 'absent') badge.className = 'status-badge status-absent';
    else badge.className = 'status-badge';
    badge.textContent = duty.status.charAt(0).toUpperCase() + duty.status.slice(1);

    detailsDiv.appendChild(title);
    detailsDiv.appendChild(meta);
    detailsDiv.appendChild(badge);

    const actions = document.createElement('div');
    actions.className = 'duty-actions';

    const viewBtn = document.createElement('button');
    viewBtn.textContent = 'View Details';
    viewBtn.onclick = (e) => toggleDetails(`${containerId}-details-${index}`, e);

    // Add attendance buttons for upcoming duties
    if (containerId === 'upcomingDutyList' && duty.status === 'assigned') {
      const presentBtn = document.createElement('button');
      presentBtn.textContent = 'Mark Present';
      presentBtn.className = 'attendance-btn present-btn';
      presentBtn.onclick = () => markAttendance(duty.id, 'present');
      
      const absentBtn = document.createElement('button');
      absentBtn.textContent = 'Mark Absent';
      absentBtn.className = 'attendance-btn absent-btn';
      absentBtn.onclick = () => markAttendance(duty.id, 'absent');
      
      actions.appendChild(presentBtn);
      actions.appendChild(absentBtn);
    }

    actions.appendChild(viewBtn);

    li.appendChild(detailsDiv);
    li.appendChild(actions);

    const expanded = document.createElement('div');
    expanded.className = 'expanded-details';
    expanded.id = `${containerId}-details-${index}`;

    expanded.innerHTML = `
      <div class="detail-section">
        <h4>Other Invigilators</h4>
        <ul class="invigilators-list">
          ${duty.other_invigilators && duty.other_invigilators.length > 0 
            ? duty.other_invigilators.map(inv => `<li>• ${inv}</li>`).join('') 
            : '<li>No other invigilators assigned</li>'}
        </ul>
      </div>
      <div class="detail-section">
        <h4>Classes in Room</h4>
        <ul class="classes-list">
          ${duty.classes && duty.classes.length > 0 
            ? duty.classes.map(cls => `<li>• ${cls}</li>`).join('') 
            : '<li>No class information available</li>'}
        </ul>
      </div>
      ${duty.notes ? `
        <div class="detail-section">
          <h4>Notes</h4>
          <p>${duty.notes}</p>
        </div>
      ` : ''}
    `;

    li.appendChild(expanded);
    container.appendChild(li);
  });
}

async function togglePastDuties() {
  const list = document.getElementById('pastDutyList');
  const isVisible = list.style.display === 'block';
  
  if (isVisible) {
    list.style.display = 'none';
  } else {
    // Show loading state
    list.innerHTML = '<li class="loading">Loading past duties...</li>';
    list.style.display = 'block';
    
    // Fetch past duties from API
    const duties = await fetchPastDuties();
    renderDuties(duties, 'pastDutyList');
  }
}

function toggleDetails(id, event) {
  const section = document.getElementById(id);
  if (section) {
    section.classList.toggle('show');
    event.target.textContent = section.classList.contains('show') ? 'Hide Details' : 'View Details';
  }
}

// Mark attendance function
async function markAttendance(dutyId, status) {
  const result = await updateDutyStatus(dutyId, status);
  
  if (result.success) {
    alert(`Attendance marked as ${status} successfully!`);
    // Refresh the upcoming duties
    const duties = await fetchUpcomingDuties();
    renderDuties(duties, 'upcomingDutyList');
  } else {
    alert(`Error: ${result.error || 'Failed to update attendance'}`);
  }
}

// Initial render: fetch upcoming duties from API
document.addEventListener('DOMContentLoaded', async () => {
  // Show loading state
  const upcomingList = document.getElementById('upcomingDutyList');
  upcomingList.innerHTML = '<li class="loading">Loading upcoming duties...</li>';
  upcomingList.style.display = 'block';
  
  // Fetch upcoming duties from API
  const duties = await fetchUpcomingDuties();
  
  if (duties.length > 0) {
    renderDuties(duties, 'upcomingDutyList');
  } else {
    upcomingList.innerHTML = '<li class="no-duties">No upcoming duties found</li>';
  }
});
