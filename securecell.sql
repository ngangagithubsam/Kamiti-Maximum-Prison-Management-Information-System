-- ============================================================
-- SecureCell Prison Management System v3.0
-- Full Database Schema — Run via phpMyAdmin or:
--   mysql -u root -p < securecell.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS securecell CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE securecell;

-- ============================================================
-- STAFF / USERS
-- ============================================================
CREATE TABLE IF NOT EXISTS staff (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  staffId     VARCHAR(20)  NOT NULL UNIQUE,
  firstname   VARCHAR(100) NOT NULL,
  lastname    VARCHAR(100) DEFAULT '',
  role        VARCHAR(80)  DEFAULT 'Officer',
  dept        VARCHAR(100) DEFAULT '',
  badge       VARCHAR(50)  DEFAULT '',
  username    VARCHAR(80)  NOT NULL UNIQUE,
  password    VARCHAR(255) NOT NULL,
  status      VARCHAR(30)  DEFAULT 'Active',
  lastlogin   DATETIME     DEFAULT NULL,
  created_at  DATETIME     DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- PRISONERS
-- ============================================================
CREATE TABLE IF NOT EXISTS prisoners (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  prisonerId  VARCHAR(20)  NOT NULL UNIQUE,
  firstname   VARCHAR(100) NOT NULL,
  middlename  VARCHAR(100) DEFAULT '',
  lastname    VARCHAR(100) DEFAULT '',
  dob         DATE         DEFAULT NULL,
  gender      VARCHAR(20)  DEFAULT '',
  natid       VARCHAR(50)  DEFAULT '',
  nationality VARCHAR(80)  DEFAULT '',
  address     TEXT         DEFAULT NULL,
  emname      VARCHAR(200) DEFAULT '',
  emphone     VARCHAR(30)  DEFAULT '',
  admdate     DATE         DEFAULT NULL,
  cell        VARCHAR(50)  DEFAULT '',
  sentence    INT          DEFAULT 0,
  `release`   DATE         DEFAULT NULL,
  offense     TEXT         DEFAULT NULL,
  security    VARCHAR(30)  DEFAULT 'Medium',
  status      VARCHAR(30)  DEFAULT 'Active',
  prisonerType VARCHAR(100) DEFAULT 'Remand / Pre-trial prisoners',
  caseNumber  VARCHAR(100) DEFAULT '',
  photo       MEDIUMTEXT   DEFAULT NULL,
  notes       TEXT         DEFAULT NULL,
  created_at  DATETIME     DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- RELEASE REQUESTS
-- ============================================================
CREATE TABLE IF NOT EXISTS release_requests (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  requestId    VARCHAR(20)  NOT NULL UNIQUE,
  prisonerId   VARCHAR(20)  NOT NULL,
  prisonerName VARCHAR(200) DEFAULT '',
  releaseType  VARCHAR(50)  DEFAULT 'Sentence Complete',
  releaseDate  DATE         DEFAULT NULL,
  reason       TEXT         DEFAULT NULL,
  notes        TEXT         DEFAULT NULL,
  status       VARCHAR(30)  DEFAULT 'Pending',
  requestedBy  VARCHAR(200) DEFAULT '',
  requestDate  DATE         DEFAULT NULL,
  decisionBy   VARCHAR(200) DEFAULT '',
  decisionDate DATE         DEFAULT NULL,
  created_at   DATETIME     DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- CELLS
-- ============================================================
CREATE TABLE IF NOT EXISTS cells (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  cellNumber  VARCHAR(20)  NOT NULL UNIQUE,
  block       VARCHAR(50)  DEFAULT '',
  capacity    INT          DEFAULT 1,
  occupied    INT          DEFAULT 0,
  status      VARCHAR(30)  DEFAULT 'Available',
  notes       TEXT         DEFAULT NULL,
  created_at  DATETIME     DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- VISITATIONS
-- ============================================================
CREATE TABLE IF NOT EXISTS visitations (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  visitId      VARCHAR(20)  NOT NULL UNIQUE,
  prisoner     VARCHAR(20)  NOT NULL,
  prisonerName VARCHAR(200) DEFAULT '',
  visitorname  VARCHAR(200) NOT NULL,
  relationship VARCHAR(80)  DEFAULT '',
  visitorid    VARCHAR(80)  DEFAULT '',
  phone        VARCHAR(30)  DEFAULT '',
  visitDate    DATETIME     DEFAULT NULL,
  duration     INT          DEFAULT 30,
  type         VARCHAR(50)  DEFAULT 'In-Person',
  status       VARCHAR(30)  DEFAULT 'Scheduled',
  notes        TEXT         DEFAULT NULL,
  created_at   DATETIME     DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- INCIDENTS
-- ============================================================
CREATE TABLE IF NOT EXISTS incidents (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  incidentId   VARCHAR(20)  NOT NULL UNIQUE,
  prisoner     VARCHAR(20)  NOT NULL,
  prisonerName VARCHAR(200) DEFAULT '',
  type         VARCHAR(80)  DEFAULT '',
  severity     VARCHAR(30)  DEFAULT 'Low',
  others       TEXT         DEFAULT NULL,
  incidentDate DATE         DEFAULT NULL,
  location     VARCHAR(200) DEFAULT '',
  description  TEXT         DEFAULT NULL,
  action       TEXT         DEFAULT NULL,
  officer      VARCHAR(200) DEFAULT '',
  status       VARCHAR(30)  DEFAULT 'Open',
  created_at   DATETIME     DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS incident_status_history (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  incident_id INT NOT NULL,
  old_status  VARCHAR(30) DEFAULT NULL,
  new_status  VARCHAR(30) NOT NULL,
  changed_by  INT NOT NULL,
  changed_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_incident_history (incident_id, changed_at),
  FOREIGN KEY (incident_id) REFERENCES incidents(id) ON DELETE CASCADE,
  FOREIGN KEY (changed_by) REFERENCES staff(id)
);

-- ============================================================
-- MOVEMENTS
-- ============================================================
CREATE TABLE IF NOT EXISTS movements (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  logId        VARCHAR(20)  NOT NULL UNIQUE,
  prisoner     VARCHAR(20)  NOT NULL,
  prisonerName VARCHAR(200) DEFAULT '',
  type         VARCHAR(50)  DEFAULT 'Check-In',
  location     VARCHAR(200) DEFAULT '',
  reason       TEXT         DEFAULT NULL,
  officer      VARCHAR(200) DEFAULT '',
  moveDate     DATETIME     DEFAULT NULL,
  notes        TEXT         DEFAULT NULL,
  created_at   DATETIME     DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- COURT CASES
-- ============================================================
CREATE TABLE IF NOT EXISTS court (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  caseId       VARCHAR(20)  NOT NULL UNIQUE,
  prisoner     VARCHAR(20)  NOT NULL,
  prisonerName VARCHAR(200) DEFAULT '',
  casenumber   VARCHAR(100) DEFAULT '',
  court        VARCHAR(200) DEFAULT '',
  charges      TEXT         DEFAULT NULL,
  judge        VARCHAR(200) DEFAULT '',
  prosecutor   VARCHAR(200) DEFAULT '',
  defense      VARCHAR(200) DEFAULT '',
  filingdate   DATE         DEFAULT NULL,
  hearingdate  DATE         DEFAULT NULL,
  verdict      VARCHAR(100) DEFAULT 'Pending',
  sentence     VARCHAR(200) DEFAULT '',
  status       VARCHAR(30)  DEFAULT 'Ongoing',
  fine         INT          DEFAULT 0,
  notes        TEXT         DEFAULT NULL,
  created_at   DATETIME     DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- REPORTS
-- ============================================================
CREATE TABLE IF NOT EXISTS reports (
  id                    INT AUTO_INCREMENT PRIMARY KEY,
  reportId              VARCHAR(20)  NOT NULL UNIQUE,
  title                 VARCHAR(300) NOT NULL,
  type                  VARCHAR(80)  DEFAULT 'General',
  prisoner              VARCHAR(20)  DEFAULT '',
  body                  TEXT         DEFAULT NULL,
  author                VARCHAR(200) DEFAULT '',
  status                VARCHAR(20)  DEFAULT 'Pending',
  department            VARCHAR(100) DEFAULT '',
  block                 VARCHAR(100) DEFAULT '',
  processing_time_hours DECIMAL(10,2) DEFAULT 0,
  reportDate            DATETIME     DEFAULT CURRENT_TIMESTAMP,
  created_at            DATETIME     DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_reports_date_status (reportDate, status),
  INDEX idx_reports_type_author (type, author)
);

-- ============================================================
-- MEDICAL RECORDS
-- ============================================================
CREATE TABLE IF NOT EXISTS medical (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  recordId     VARCHAR(20)  NOT NULL UNIQUE,
  prisoner     VARCHAR(20)  NOT NULL,
  prisonerName VARCHAR(200) DEFAULT '',
  `condition`  VARCHAR(300) DEFAULT '',
  treatment    TEXT         DEFAULT NULL,
  doctor       VARCHAR(200) DEFAULT '',
  medDate      DATE         DEFAULT NULL,
  status       VARCHAR(30)  DEFAULT 'Active',
  notes        TEXT         DEFAULT NULL,
  created_at   DATETIME     DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- SHIFT MANAGEMENT  (NEW)
-- ============================================================
CREATE TABLE IF NOT EXISTS shifts (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  shiftId      VARCHAR(20)  NOT NULL UNIQUE,
  staffId      VARCHAR(20)  NOT NULL,
  staffName    VARCHAR(200) DEFAULT '',
  shiftType    VARCHAR(50)  DEFAULT 'Morning',   -- Morning|Afternoon|Night|Emergency
  shiftDate    DATE         NOT NULL,
  startTime    TIME         NOT NULL,
  endTime      TIME         NOT NULL,
  location     VARCHAR(200) DEFAULT '',           -- post/block assignment
  status       VARCHAR(30)  DEFAULT 'Scheduled', -- Scheduled|Active|Completed|Absent|Swapped
  supervisor   VARCHAR(200) DEFAULT '',
  notes        TEXT         DEFAULT NULL,
  created_at   DATETIME     DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS shift_swaps (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  swapId       VARCHAR(20)  NOT NULL UNIQUE,
  originalShift INT         NOT NULL,             -- FK → shifts.id
  requestedBy  VARCHAR(200) DEFAULT '',
  swapWith     VARCHAR(200) DEFAULT '',
  reason       TEXT         DEFAULT NULL,
  status       VARCHAR(30)  DEFAULT 'Pending',   -- Pending|Approved|Rejected
  approvedBy   VARCHAR(200) DEFAULT '',
  requestDate  DATETIME     DEFAULT CURRENT_TIMESTAMP,
  created_at   DATETIME     DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- INVENTORY  (NEW)
-- ============================================================
CREATE TABLE IF NOT EXISTS inventory_items (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  itemCode     VARCHAR(30)  NOT NULL UNIQUE,
  name         VARCHAR(200) NOT NULL,
  category     VARCHAR(80)  DEFAULT 'General',   -- Equipment|Medical|Food|Uniform|Cleaning|Office|Security|Other
  description  TEXT         DEFAULT NULL,
  unit         VARCHAR(30)  DEFAULT 'pcs',
  quantity     INT          DEFAULT 0,
  minQuantity  INT          DEFAULT 5,            -- reorder threshold
  maxQuantity  INT          DEFAULT 100,
  unitCost     DECIMAL(10,2) DEFAULT 0.00,
  supplier     VARCHAR(200) DEFAULT '',
  location     VARCHAR(100) DEFAULT '',           -- storage location
  status       VARCHAR(30)  DEFAULT 'Available', -- Available|Low Stock|Out of Stock|Discontinued
  lastRestocked DATE         DEFAULT NULL,
  created_at   DATETIME     DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS inventory_transactions (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  txnId        VARCHAR(20)  NOT NULL UNIQUE,
  itemId       INT          NOT NULL,
  itemCode     VARCHAR(30)  DEFAULT '',
  itemName     VARCHAR(200) DEFAULT '',
  type         VARCHAR(30)  DEFAULT 'Issue',     -- Issue|Return|Restock|Damaged|Disposed|Transfer
  quantity     INT          DEFAULT 0,
  balanceAfter INT          DEFAULT 0,
  issuedTo     VARCHAR(200) DEFAULT '',          -- prisoner ID or staff name
  purpose      VARCHAR(300) DEFAULT '',
  authorizedBy VARCHAR(200) DEFAULT '',
  txnDate      DATETIME     DEFAULT CURRENT_TIMESTAMP,
  notes        TEXT         DEFAULT NULL,
  created_at   DATETIME     DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- REHABILITATION PROGRAMS  (NEW)
-- ============================================================
CREATE TABLE IF NOT EXISTS rehab_programs (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  programId    VARCHAR(20)  NOT NULL UNIQUE,
  name         VARCHAR(200) NOT NULL,
  type         VARCHAR(80)  DEFAULT 'Education', -- Education|Vocational|Counseling|Drug Rehab|Religious|Sports|Other
  description  TEXT         DEFAULT NULL,
  facilitator  VARCHAR(200) DEFAULT '',
  schedule     VARCHAR(200) DEFAULT '',          -- e.g. "Mon/Wed/Fri 09:00-11:00"
  venue        VARCHAR(200) DEFAULT '',
  capacity     INT          DEFAULT 20,
  enrolled     INT          DEFAULT 0,
  startDate    DATE         DEFAULT NULL,
  endDate      DATE         DEFAULT NULL,
  status       VARCHAR(30)  DEFAULT 'Active',   -- Active|Completed|Suspended|Upcoming
  created_at   DATETIME     DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS rehab_enrollments (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  enrollId     VARCHAR(20)  NOT NULL UNIQUE,
  programId    INT          NOT NULL,
  programName  VARCHAR(200) DEFAULT '',
  prisoner     VARCHAR(20)  NOT NULL,
  prisonerName VARCHAR(200) DEFAULT '',
  enrollDate   DATE         DEFAULT NULL,
  completionDate DATE       DEFAULT NULL,
  progress     INT          DEFAULT 0,          -- 0-100 %
  grade        VARCHAR(10)  DEFAULT '',
  status       VARCHAR(30)  DEFAULT 'Active',   -- Active|Completed|Dropped|Suspended
  notes        TEXT         DEFAULT NULL,
  created_at   DATETIME     DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- PRISONER PROGRESS HISTORY (append-only assessments)
-- ============================================================
CREATE TABLE IF NOT EXISTS prisoner_progress (
  id               INT AUTO_INCREMENT PRIMARY KEY,
  prisoner_id      INT NOT NULL,
  assessment_date  DATE NOT NULL,
  category         VARCHAR(40) NOT NULL,
  activity         VARCHAR(200) NOT NULL,
  previous_score   DECIMAL(5,2) DEFAULT NULL,
  current_score    DECIMAL(5,2) DEFAULT NULL,
  change_summary   VARCHAR(255) DEFAULT '',
  comments         TEXT DEFAULT NULL,
  recommendations  TEXT DEFAULT NULL,
  next_review_date DATE DEFAULT NULL,
  created_by       INT NOT NULL,
  created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_progress_prisoner_date (prisoner_id, assessment_date),
  FOREIGN KEY (prisoner_id) REFERENCES prisoners(id),
  FOREIGN KEY (created_by) REFERENCES staff(id)
);

-- ============================================================
-- AUDIT LOG
-- ============================================================
CREATE TABLE IF NOT EXISTS audit_log (
  id       INT AUTO_INCREMENT PRIMARY KEY,
  user     VARCHAR(200) DEFAULT '',
  action   VARCHAR(50)  DEFAULT '',
  module   VARCHAR(100) DEFAULT '',
  details  TEXT         DEFAULT NULL,
  ts       DATETIME     DEFAULT CURRENT_TIMESTAMP,
  ip       VARCHAR(45)  DEFAULT ''
);

-- ============================================================
-- SEED DATA
-- ============================================================

-- Default admin (password = admin123)
INSERT INTO staff (staffId, firstname, lastname, role, dept, badge, username, password, status)
VALUES ('STF-001','System','Administrator','Administrator','Management','ADM-001','admin',
  '$2y$10$/.J5iY00Vfu1kOGA5v873OBHLxgQb4tUbR704pNTkSi/ifBzFUUCG','Active')
ON DUPLICATE KEY UPDATE password=VALUES(password), status=VALUES(status);

-- Sample staff
INSERT INTO staff (staffId, firstname, lastname, role, dept, badge, username, password, status) VALUES
('STF-002','Daniel','Waweru','Warden','Security','WRD-001','warden','$2y$10$/.J5iY00Vfu1kOGA5v873OBHLxgQb4tUbR704pNTkSi/ifBzFUUCG','Active'),
('STF-003','Esther','Kamau','Officer','Security','OFF-001','officer','$2y$10$/.J5iY00Vfu1kOGA5v873OBHLxgQb4tUbR704pNTkSi/ifBzFUUCG','Active'),
('STF-004','Dr. Robert','Mwangi','Medical','Health','MED-001','medical','$2y$10$/.J5iY00Vfu1kOGA5v873OBHLxgQb4tUbR704pNTkSi/ifBzFUUCG','Active'),
('STF-005','Faith','Njeri','Officer','Security','OFF-002','faithn','$2y$10$/.J5iY00Vfu1kOGA5v873OBHLxgQb4tUbR704pNTkSi/ifBzFUUCG','Active')
ON DUPLICATE KEY UPDATE password=VALUES(password), status=VALUES(status);

-- Sample cells
INSERT IGNORE INTO cells (cellNumber, block, capacity, occupied, status) VALUES
('A-101','Alpha Block',2,2,'Full'),
('A-102','Alpha Block',2,1,'Available'),
('A-103','Alpha Block',1,0,'Available'),
('B-201','Bravo Block',2,2,'Full'),
('B-202','Bravo Block',2,1,'Available'),
('C-301','Charlie Block',4,2,'Available'),
('D-401','Delta Block',1,0,'Available'),
('ISO-1','Isolation',1,1,'Full');

-- Sample prisoners
INSERT IGNORE INTO prisoners (prisonerId,firstname,lastname,dob,gender,natid,nationality,address,admdate,cell,sentence,`release`,offense,security,status,notes) VALUES
('PRI-001','John','Mutua','1985-03-12','Male','12345678','Kenyan','Nairobi, Kenya','2022-01-15','A-101',36,'2025-01-15','Armed Robbery','Maximum','Active','Gang affiliation noted'),
('PRI-002','Grace','Achieng','1992-07-22','Female','98765432','Kenyan','Kisumu, Kenya','2023-06-01','A-102',24,'2025-06-01','Fraud','Minimum','Active',''),
('PRI-003','Samuel','Kariuki','1978-11-05','Male','45678901','Kenyan','Mombasa, Kenya','2021-09-20','B-201',60,'2026-09-20','Murder','Maximum','Active','High-risk inmate'),
('PRI-004','Faith','Ndungu','1995-02-14','Female','56789012','Kenyan','Nakuru, Kenya','2023-11-10','B-202',12,'2024-11-10','Drug Possession','Medium','Active',''),
('PRI-005','James','Otieno','1988-09-30','Male','67890123','Kenyan','Eldoret, Kenya','2020-04-05','C-301',84,'2027-04-05','Drug Trafficking','Maximum','Active','Cooperative');

-- Sample shifts
INSERT IGNORE INTO shifts (shiftId,staffId,staffName,shiftType,shiftDate,startTime,endTime,location,status,supervisor) VALUES
('SHF-001','STF-003','Esther Kamau','Morning','2025-01-20','06:00:00','14:00:00','Block A','Completed','Daniel Waweru'),
('SHF-002','STF-005','Faith Njeri','Afternoon','2025-01-20','14:00:00','22:00:00','Main Gate','Completed','Daniel Waweru'),
('SHF-003','STF-003','Esther Kamau','Night','2025-01-20','22:00:00','06:00:00','Block B','Completed','Daniel Waweru'),
('SHF-004','STF-005','Faith Njeri','Morning','2025-01-21','06:00:00','14:00:00','Block A','Scheduled','Daniel Waweru');

-- Sample inventory
INSERT IGNORE INTO inventory_items (itemCode,name,category,unit,quantity,minQuantity,maxQuantity,unitCost,supplier,location,status,lastRestocked) VALUES
('INV-001','Handcuffs','Security','pcs',45,10,60,'2500.00','Nairobi Security Supplies','Armory','Available','2025-01-01'),
('INV-002','Batons','Security','pcs',30,10,40,'1200.00','Nairobi Security Supplies','Armory','Available','2025-01-01'),
('INV-003','Prison Uniforms (Male)','Uniform','pcs',120,30,200,'800.00','KSA Textiles','Stores Block A','Available','2025-01-05'),
('INV-004','Prison Uniforms (Female)','Uniform','pcs',60,20,100,'800.00','KSA Textiles','Stores Block B','Available','2025-01-05'),
('INV-005','Paracetamol 500mg','Medical','tabs',500,100,1000,'5.00','Medi-Pharm Kenya','Medical Bay','Available','2025-01-10'),
('INV-006','Antiseptic Solution','Medical','litres',15,5,30,'350.00','Medi-Pharm Kenya','Medical Bay','Available','2025-01-10'),
('INV-007','Maize Flour (10kg)','Food','bags',80,20,150,'1200.00','Unga Mills','Kitchen Store','Available','2025-01-12'),
('INV-008','Beans (10kg)','Food','bags',60,20,120,'900.00','Nairobi Grains','Kitchen Store','Available','2025-01-12'),
('INV-009','Disinfectant (20L)','Cleaning','drums',8,3,20,'2800.00','Clean-Pro Supplies','Cleaning Store','Low Stock','2024-12-20'),
('INV-010','Mop Sets','Cleaning','sets',3,5,15,'450.00','Clean-Pro Supplies','Cleaning Store','Low Stock','2024-11-01');

-- Sample rehab programs
INSERT IGNORE INTO rehab_programs (programId,name,type,description,facilitator,schedule,venue,capacity,enrolled,startDate,endDate,status) VALUES
('PRG-001','Basic Literacy & Numeracy','Education','Foundational reading, writing and arithmetic for inmates','Jane Muthoni','Mon/Wed/Fri 08:00-10:00','Education Block Room 1',20,12,'2024-09-01','2025-06-30','Active'),
('PRG-002','Carpentry & Joinery','Vocational','Practical woodwork skills leading to a certificate','Moses Kiprop','Tue/Thu 08:00-12:00','Workshop Block',15,8,'2024-09-01','2025-06-30','Active'),
('PRG-003','Substance Abuse Counseling','Drug Rehab','Group therapy and individual counseling sessions','Dr. Ann Wanjiru','Mon/Wed 14:00-16:00','Counseling Room 2',10,6,'2024-10-01','2025-09-30','Active'),
('PRG-004','Tailoring & Fashion Design','Vocational','Sewing and garment making skills','Grace Omondi','Mon-Fri 09:00-11:00','Workshop Block',12,5,'2024-11-01','2025-10-31','Active'),
('PRG-005','Religious & Moral Education','Religious','Spiritual guidance and ethical values','Rev. Peter Njiru','Sat 10:00-12:00','Chapel',50,35,'2024-01-01','2025-12-31','Active');
