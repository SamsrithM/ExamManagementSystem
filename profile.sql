CREATE TABLE faculty_profile (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  designation VARCHAR(100) NOT NULL,
  department VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL,
  phone VARCHAR(20) NOT NULL,
  research TEXT,
  image_url VARCHAR(300) DEFAULT NULL
);

INSERT INTO faculty_profile (name, designation, department, email, phone, research, image_url)
VALUES (
  'Prof. V. Siva Rama Krishnaiah',
  'Visiting Professor',
  'Computer Science & Engineering',
  'vsrk@iiitk.ac.in',
  '+91 9899750010',
  'Entrepreneurship and Management Functions, Software Project Management, Soft Skills, Knowledge Management, Professional Ethics and Human Values',
  'https://lh7-us.googleusercontent.com/docsz/AD_4nXe93YfBpezpl67rPtmgY3zcUXxv94GBFIrREMIUWxEhz5-czRioy7yeASFJuRri71gpzj392Whwlewf08O5-xe8bBrW8zCQmguiLcAG3VC4zA7le0YiqzI-AgtMGA9_wzY6a_Zbmyz7WNwbfT4pF76Als_E?key=1YFjf14dZyqe92eDQgoXgQ'
);
