const upcomingDuties = [
  {
    title: "Midterm Exam",
    date: "2025-10-13",
    time: "10:00 AM - 12:00 PM",
    venue: "Lecture Hall A-101",
    status: "Upcoming",
    otherInvigilators: ["Dr. John Doe", "Prof. Jane Smith"],
    classes: ["Class A (CS101 - 50 students)", "Class B (CS102 - 45 students)"]
  },
  {
    title: "Quiz",
    date: "2025-10-25", // Will be ignored (outside 7 days)
    time: "02:00 PM - 03:00 PM",
    venue: "Seminar Room B-205",
    status: "Upcoming",
    otherInvigilators: ["Prof. Alice Johnson", "Dr. Bob Wilson"],
    classes: ["Class C (CS201 - 60 students)", "Class D (CS202 - 55 students)"]
  }
];

const pastDuties = [
  {
    title: "Final Exam",
    date: "2025-09-15",
    time: "09:00 AM - 11:00 AM",
    venue: "Auditorium Main Hall",
    status: "Present",
    otherInvigilators: ["Dr. Emily Carter", "Prof. Mike Lee"],
    classes: ["Class F (MA101 - 80 students)", "Class G (PH102 - 70 students)"]
  },
  {
    title: "Midterm Quiz",
    date: "2025-10-05",
    time: "11:00 AM - 12:00 PM",
    venue: "Lab Room C-110",
    status: "Absent",
    otherInvigilators: ["Prof. Sarah Kim", "Dr. David Brown"],
    classes: ["Class H (EE201 - 30 students)"]
  }
];

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
      📅 ${new Date(duty.date).toLocaleDateString()} &nbsp; 🕒 ${duty.time} &nbsp; 📍 ${duty.venue}
    `;

    const badge = document.createElement('div');
    if (duty.status === 'Present') badge.className = 'status-badge status-present';
    else if (duty.status === 'Absent') badge.className = 'status-badge status-absent';
    else badge.className = 'status-badge';
    badge.textContent = duty.status;

    detailsDiv.appendChild(title);
    detailsDiv.appendChild(meta);
    detailsDiv.appendChild(badge);

    const actions = document.createElement('div');
    actions.className = 'duty-actions';

    const viewBtn = document.createElement('button');
    viewBtn.textContent = 'View Details';
    viewBtn.onclick = (e) => toggleDetails(`${containerId}-details-${index}`, e);

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
          ${duty.otherInvigilators.map(inv => `<li>• ${inv}</li>`).join('')}
        </ul>
      </div>
      <div class="detail-section">
        <h4>Classes in Room</h4>
        <ul class="classes-list">
          ${duty.classes.map(cls => `<li>• ${cls}</li>`).join('')}
        </ul>
      </div>
    `;

    li.appendChild(expanded);
    container.appendChild(li);
  });
}

function togglePastDuties() {
  const list = document.getElementById('pastDutyList');
  const isVisible = list.style.display === 'block';
  if (isVisible) {
    list.style.display = 'none';
  } else {
    renderDuties(pastDuties, 'pastDutyList');
    list.style.display = 'block';
  }
}

function toggleDetails(id, event) {
  const section = document.getElementById(id);
  if (section) {
    section.classList.toggle('show');
    event.target.textContent = section.classList.contains('show') ? 'Hide Details' : 'View Details';
  }
}

// Initial render: only if upcoming duties are within 7 days
document.addEventListener('DOMContentLoaded', () => {
  const upcoming = upcomingDuties.filter(duty => isWithinNext7Days(duty.date));
  if (upcoming.length > 0) {
    renderDuties(upcoming, 'upcomingDutyList');
    document.getElementById('upcomingDutyList').style.display = 'block';
  }
});
