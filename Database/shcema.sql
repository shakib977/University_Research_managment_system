-- ============================================================
-- URMS — COMPLETE ORACLE SETUP SCRIPT
-- University Research Management System
-- Course: Advanced Database Management System, Spring 2025-26
-- Run as: project_user / project123
-- ============================================================


-- ============================================================
-- PART 1: DROP EXISTING OBJECTS (safe re-run)
-- ============================================================

BEGIN
    -- Drop tables in reverse FK order
    FOR t IN (
        SELECT table_name FROM user_tables
        WHERE table_name IN (
            'AUTHORS','WORK','PUBLICATION','CATEGORY',
            'PUBLICATION_TYPE','PROJECT','CONTACT',
            'RESEARCHER','FUND_AGENCY','SUPERVISOR',
            'DEPARTMENT','LOCATION','ADMIN'
        )
    ) LOOP
        EXECUTE IMMEDIATE 'DROP TABLE ' || t.table_name || ' CASCADE CONSTRAINTS';
    END LOOP;
END;
/

BEGIN
    FOR s IN (
        SELECT sequence_name FROM user_sequences
        WHERE sequence_name IN (
            'SEQ_ADMIN','SEQ_RESEARCHER','SEQ_LOCATION',
            'SEQ_DEPARTMENT','SEQ_PROJECT','SEQ_SUPERVISOR',
            'SEQ_FUND','SEQ_PUBLICATION','SEQ_CATEGORY','SEQ_TYPE'
        )
    ) LOOP
        EXECUTE IMMEDIATE 'DROP SEQUENCE ' || s.sequence_name;
    END LOOP;
END;
/


-- ============================================================
-- PART 2: SEQUENCES
-- ============================================================

CREATE SEQUENCE seq_admin       START WITH 1 INCREMENT BY 1 NOCACHE;
CREATE SEQUENCE seq_researcher  START WITH 1 INCREMENT BY 1 NOCACHE;
CREATE SEQUENCE seq_location    START WITH 1 INCREMENT BY 1 NOCACHE;
CREATE SEQUENCE seq_department  START WITH 1 INCREMENT BY 1 NOCACHE;
CREATE SEQUENCE seq_project     START WITH 1 INCREMENT BY 1 NOCACHE;
CREATE SEQUENCE seq_supervisor  START WITH 1 INCREMENT BY 1 NOCACHE;
CREATE SEQUENCE seq_fund        START WITH 1 INCREMENT BY 1 NOCACHE;
CREATE SEQUENCE seq_publication START WITH 1 INCREMENT BY 1 NOCACHE;
CREATE SEQUENCE seq_category    START WITH 1 INCREMENT BY 1 NOCACHE;
CREATE SEQUENCE seq_type        START WITH 1 INCREMENT BY 1 NOCACHE;


-- ============================================================
-- PART 3: TABLES
-- Order matters — parent tables first (FK dependencies)
-- ============================================================

-- 1. Admin
CREATE TABLE Admin (
    A_Id     NUMBER PRIMARY KEY,
    Name     VARCHAR2(100) NOT NULL,
    Email    VARCHAR2(100) UNIQUE NOT NULL,
    Password VARCHAR2(100) NOT NULL
);

-- 2. Location
CREATE TABLE Location (
    X_Id    NUMBER PRIMARY KEY,
    City    VARCHAR2(100) NOT NULL,
    Country VARCHAR2(100) NOT NULL
);

-- 3. Department
CREATE TABLE Department (
    D_Id       NUMBER PRIMARY KEY,
    Building   VARCHAR2(100) NOT NULL,
    D_Name     VARCHAR2(100) NOT NULL,
    Office_Phn VARCHAR2(20)  NOT NULL
);

-- 4. Researcher (depends on Admin, Location, Department)
CREATE TABLE Researcher (
    R_Id        NUMBER PRIMARY KEY,
    Name        VARCHAR2(100) NOT NULL,
    Password    VARCHAR2(100) NOT NULL,
    Re_Area     VARCHAR2(100),
    Designation VARCHAR2(100) NOT NULL,
    A_Id        NUMBER NOT NULL,
    X_Id        NUMBER NOT NULL,
    D_Id        NUMBER NOT NULL,
    CONSTRAINT fk_res_admin      FOREIGN KEY (A_Id) REFERENCES Admin(A_Id),
    CONSTRAINT fk_res_location   FOREIGN KEY (X_Id) REFERENCES Location(X_Id),
    CONSTRAINT fk_res_department FOREIGN KEY (D_Id) REFERENCES Department(D_Id)
);

-- 5. Contact (depends on Researcher) — multivalued phone
CREATE TABLE Contact (
    R_Id    NUMBER       NOT NULL,
    Con_Phn VARCHAR2(20) NOT NULL,
    CONSTRAINT pk_contact PRIMARY KEY (R_Id, Con_Phn),
    CONSTRAINT fk_contact_res FOREIGN KEY (R_Id) REFERENCES Researcher(R_Id)
);

-- 6. Supervisor
CREATE TABLE Supervisor (
    S_Id        NUMBER PRIMARY KEY,
    Email       VARCHAR2(100) NOT NULL,
    S_Name      VARCHAR2(100) NOT NULL,
    Phn         VARCHAR2(20)  NOT NULL,
    Designation VARCHAR2(100) NOT NULL
);

-- 7. Fund_Agency
CREATE TABLE Fund_Agency (
    F_Id      NUMBER PRIMARY KEY,
    F_Name    VARCHAR2(100) NOT NULL,
    Country   VARCHAR2(100) NOT NULL,
    Con_Email VARCHAR2(100) NOT NULL,
    Phone     VARCHAR2(20)  NOT NULL
);

-- 8. Project (depends on Supervisor, Fund_Agency)
CREATE TABLE Project (
    Pro_Id NUMBER PRIMARY KEY,
    Title  VARCHAR2(200) NOT NULL,
    S_Date DATE          NOT NULL,
    E_Date DATE,
    Status VARCHAR2(50)  NOT NULL,
    S_Id   NUMBER        NOT NULL,
    F_Id   NUMBER        NOT NULL,
    CONSTRAINT fk_proj_supervisor FOREIGN KEY (S_Id) REFERENCES Supervisor(S_Id),
    CONSTRAINT fk_proj_fund       FOREIGN KEY (F_Id) REFERENCES Fund_Agency(F_Id)
);

-- 9. Work — junction table (Researcher ↔ Project)
CREATE TABLE Work (
    Pro_Id NUMBER NOT NULL,
    R_Id   NUMBER NOT NULL,
    CONSTRAINT pk_work          PRIMARY KEY (Pro_Id, R_Id),
    CONSTRAINT fk_work_project  FOREIGN KEY (Pro_Id) REFERENCES Project(Pro_Id),
    CONSTRAINT fk_work_res      FOREIGN KEY (R_Id)   REFERENCES Researcher(R_Id)
);

-- 10. Category
CREATE TABLE Category (
    C_Id        NUMBER PRIMARY KEY,
    Description VARCHAR2(200) NOT NULL,
    C_Name      VARCHAR2(100) NOT NULL
);

-- 11. Publication_Type
CREATE TABLE Publication_Type (
    T_Id NUMBER PRIMARY KEY,
    Type VARCHAR2(100) NOT NULL
);

-- 12. Publication (depends on Category, Publication_Type)
CREATE TABLE Publication (
    P_Id      NUMBER PRIMARY KEY,
    Title     VARCHAR2(200) NOT NULL,
    DOI       VARCHAR2(100) NOT NULL,
    Publisher VARCHAR2(100) NOT NULL,
    Year      NUMBER        NOT NULL,
    C_Id      NUMBER        NOT NULL,
    T_Id      NUMBER        NOT NULL,
    CONSTRAINT fk_pub_category FOREIGN KEY (C_Id) REFERENCES Category(C_Id),
    CONSTRAINT fk_pub_type     FOREIGN KEY (T_Id) REFERENCES Publication_Type(T_Id)
);

-- 13. Authors — junction table (Researcher ↔ Publication)
CREATE TABLE Authors (
    R_Id NUMBER NOT NULL,
    P_Id NUMBER NOT NULL,
    CONSTRAINT pk_authors       PRIMARY KEY (R_Id, P_Id),
    CONSTRAINT fk_auth_res      FOREIGN KEY (R_Id) REFERENCES Researcher(R_Id),
    CONSTRAINT fk_auth_pub      FOREIGN KEY (P_Id) REFERENCES Publication(P_Id)
);


-- ============================================================
-- PART 4: SAMPLE DATA
-- ============================================================

-- Admin
INSERT INTO Admin VALUES (seq_admin.NEXTVAL, 'Saiful Alam',         'saiful@mail.com',  'pass123');
INSERT INTO Admin VALUES (seq_admin.NEXTVAL, 'Saima Ahmed Tanjila', 'saima@mail.com',   'pass456');
INSERT INTO Admin VALUES (seq_admin.NEXTVAL, 'Diganta Bepari',      'diganta@mail.com', 'pass789');
INSERT INTO Admin VALUES (seq_admin.NEXTVAL, 'Sazia Hassan Upoma',  'upoma@mail.com',   'pass321');
INSERT INTO Admin VALUES (seq_admin.NEXTVAL, 'Jahidul Islam Shakib','sakib@mail.com',   'pass654');
INSERT INTO Admin VALUES (seq_admin.NEXTVAL, 'Mohammad Tachvir',    'tachvir@mail.com', 'pass659');

-- Location
INSERT INTO Location VALUES (seq_location.NEXTVAL, 'Dhanmondi',    'Dhaka');
INSERT INTO Location VALUES (seq_location.NEXTVAL, 'Bashundhara',  'Dhaka');
INSERT INTO Location VALUES (seq_location.NEXTVAL, 'Kuratoli',     'Dhaka');
INSERT INTO Location VALUES (seq_location.NEXTVAL, 'Uttara',       'Dhaka');
INSERT INTO Location VALUES (seq_location.NEXTVAL, 'Azimpur',      'Dhaka');
INSERT INTO Location VALUES (seq_location.NEXTVAL, 'Nilkhet',      'Dhaka');

-- Department
INSERT INTO Department VALUES (seq_department.NEXTVAL, 'ECE Building, Block A', 'Computer Science & Engineering',       '+880-2-9665650');
INSERT INTO Department VALUES (seq_department.NEXTVAL, 'ECE Building, Block B', 'Electrical & Electronic Engineering',  '+880-2-9665651');
INSERT INTO Department VALUES (seq_department.NEXTVAL, 'Mechanical Building',   'Mechanical Engineering',               '+880-2-9665652');
INSERT INTO Department VALUES (seq_department.NEXTVAL, 'Medical Science Block', 'Biomedical Engineering',               '+880-2-9665653');
INSERT INTO Department VALUES (seq_department.NEXTVAL, 'Civil Building',        'Civil Engineering',                    '+880-2-9665654');
INSERT INTO Department VALUES (seq_department.NEXTVAL, 'Science Block A',       'Mathematics & Physics',                '+880-2-9665655');

-- Researcher
INSERT INTO Researcher VALUES (seq_researcher.NEXTVAL, 'Dr. Rahim',   'p1', 'Artificial Intelligence', 'Professor',          1, 1, 1);
INSERT INTO Researcher VALUES (seq_researcher.NEXTVAL, 'Dr. Karim',   'p2', 'Robotics',                'Associate Prof',     2, 2, 2);
INSERT INTO Researcher VALUES (seq_researcher.NEXTVAL, 'Ms. Sara',    'p3', 'Data Science',            'Lecturer',           3, 3, 3);
INSERT INTO Researcher VALUES (seq_researcher.NEXTVAL, 'Mr. Abid',    'p4', 'Machine Learning',        'Assistant Professor',4, 4, 4);
INSERT INTO Researcher VALUES (seq_researcher.NEXTVAL, 'Dr. Nasim',   'p5', 'Database Systems',        'Professor',          5, 5, 5);

-- Contact
INSERT INTO Contact VALUES (1, '01911223344');
INSERT INTO Contact VALUES (1, '01511223344');
INSERT INTO Contact VALUES (2, '01811223344');
INSERT INTO Contact VALUES (3, '01711223344');
INSERT INTO Contact VALUES (4, '01611223344');

-- Supervisor
INSERT INTO Supervisor VALUES (seq_supervisor.NEXTVAL, 'sup1@buet.ac.bd', 'Dr. Zafar',  '01711234567', 'Professor');
INSERT INTO Supervisor VALUES (seq_supervisor.NEXTVAL, 'sup2@buet.ac.bd', 'Dr. Monir',  '01811345678', 'Associate Professor');
INSERT INTO Supervisor VALUES (seq_supervisor.NEXTVAL, 'sup3@buet.ac.bd', 'Dr. Helen',  '01911456789', 'Senior Researcher');
INSERT INTO Supervisor VALUES (seq_supervisor.NEXTVAL, 'sup4@buet.ac.bd', 'Dr. Afsar',  '01612567890', 'Dean');
INSERT INTO Supervisor VALUES (seq_supervisor.NEXTVAL, 'sup5@buet.ac.bd', 'Dr. Yuki',   '01712678901', 'Head of Research');

-- Fund Agency
INSERT INTO Fund_Agency VALUES (seq_fund.NEXTVAL, 'University Grants Commission (UGC)', 'Bangladesh', 'grants@ugc.gov.bd',    '+880-2-9112186');
INSERT INTO Fund_Agency VALUES (seq_fund.NEXTVAL, 'ICT Division, Govt. of Bangladesh',  'Bangladesh', 'research@ict.gov.bd',  '+880-2-9145052');
INSERT INTO Fund_Agency VALUES (seq_fund.NEXTVAL, 'Bangladesh Natl Science & Tech Fdn', 'Bangladesh', 'info@bnstf.gov.bd',    '+880-2-9555123');
INSERT INTO Fund_Agency VALUES (seq_fund.NEXTVAL, 'Ministry of Environment & Forests',  'Bangladesh', 'grants@moef.gov.bd',   '+880-2-9570011');
INSERT INTO Fund_Agency VALUES (seq_fund.NEXTVAL, 'NASA Research Division',             'USA',        'nasa@research.gov',    '+11-222-3334444');

-- Project
INSERT INTO Project VALUES (seq_project.NEXTVAL, 'AI-Powered Medical Diagnosis System',  TO_DATE('2024-01-15','YYYY-MM-DD'), TO_DATE('2025-12-31','YYYY-MM-DD'), 'Active',    1, 1);
INSERT INTO Project VALUES (seq_project.NEXTVAL, 'Sustainable Urban Infrastructure',     TO_DATE('2023-09-01','YYYY-MM-DD'), TO_DATE('2025-08-31','YYYY-MM-DD'), 'Active',    2, 2);
INSERT INTO Project VALUES (seq_project.NEXTVAL, 'Quantum Computing Applications',       TO_DATE('2024-03-01','YYYY-MM-DD'), NULL,                               'Active',    3, 3);
INSERT INTO Project VALUES (seq_project.NEXTVAL, 'Climate Change Modeling System',       TO_DATE('2024-02-01','YYYY-MM-DD'), TO_DATE('2026-01-31','YYYY-MM-DD'), 'Planning',  4, 4);
INSERT INTO Project VALUES (seq_project.NEXTVAL, 'Blockchain Security Framework',        TO_DATE('2023-11-01','YYYY-MM-DD'), TO_DATE('2025-10-31','YYYY-MM-DD'), 'Active',    5, 5);

-- Work
INSERT INTO Work VALUES (1, 1);
INSERT INTO Work VALUES (1, 2);
INSERT INTO Work VALUES (2, 3);
INSERT INTO Work VALUES (3, 4);
INSERT INTO Work VALUES (4, 5);
INSERT INTO Work VALUES (5, 1);

-- Category
INSERT INTO Category VALUES (seq_category.NEXTVAL, 'Research in Artificial Intelligence and ML systems',          'Artificial Intelligence');
INSERT INTO Category VALUES (seq_category.NEXTVAL, 'Machine Learning algorithms and deep learning applications',   'Machine Learning');
INSERT INTO Category VALUES (seq_category.NEXTVAL, 'Image processing, object detection, visual recognition',       'Computer Vision');
INSERT INTO Category VALUES (seq_category.NEXTVAL, 'Big data analytics, data mining, and data warehousing',        'Data Science');
INSERT INTO Category VALUES (seq_category.NEXTVAL, 'Network security, cryptography, and cyber threats',            'Cybersecurity');

-- Publication_Type
INSERT INTO Publication_Type VALUES (seq_type.NEXTVAL, 'Journal');
INSERT INTO Publication_Type VALUES (seq_type.NEXTVAL, 'Conference');
INSERT INTO Publication_Type VALUES (seq_type.NEXTVAL, 'Workshop');
INSERT INTO Publication_Type VALUES (seq_type.NEXTVAL, 'Book Chapter');
INSERT INTO Publication_Type VALUES (seq_type.NEXTVAL, 'Peer Reviewed');

-- Publication
INSERT INTO Publication VALUES (seq_publication.NEXTVAL, 'Deep Learning for Medical Image Analysis: A Comprehensive Review', '10.1000/ai-health-2024',  'Nature',    2024, 1, 1);
INSERT INTO Publication VALUES (seq_publication.NEXTVAL, 'Smart Cities: A Comprehensive Review of IoT Applications',         '10.1000/iot-city-2024',   'IEEE',      2024, 2, 2);
INSERT INTO Publication VALUES (seq_publication.NEXTVAL, 'Quantum Algorithms for Optimization Problems',                     '10.1000/quantum-opt-2024','Springer',  2024, 3, 1);
INSERT INTO Publication VALUES (seq_publication.NEXTVAL, 'Climate Data Analytics Using Machine Learning Techniques',         '10.1000/climate-ml-2024', 'Elsevier',  2024, 4, 4);
INSERT INTO Publication VALUES (seq_publication.NEXTVAL, 'Blockchain Security: Challenges and Solutions',                    '10.1000/blockchain-2024', 'ACM',       2024, 5, 2);

-- Authors
INSERT INTO Authors VALUES (1, 1);
INSERT INTO Authors VALUES (2, 2);
INSERT INTO Authors VALUES (3, 3);
INSERT INTO Authors VALUES (4, 4);
INSERT INTO Authors VALUES (5, 5);
INSERT INTO Authors VALUES (1, 5);

COMMIT;


-- ============================================================
-- PART 5: STORED FUNCTIONS (with EXCEPTION handling)
-- ============================================================

CREATE OR REPLACE FUNCTION total_researchers
RETURN NUMBER IS
    v_total NUMBER;
BEGIN
    SELECT COUNT(*) INTO v_total FROM Researcher;
    RETURN v_total;
EXCEPTION
    WHEN OTHERS THEN
        RAISE_APPLICATION_ERROR(-20001, 'Error in total_researchers: ' || SQLERRM);
END;
/

CREATE OR REPLACE FUNCTION get_pub_count
RETURN NUMBER IS
    v_count NUMBER;
BEGIN
    SELECT COUNT(*) INTO v_count FROM Publication;
    RETURN v_count;
EXCEPTION
    WHEN OTHERS THEN
        RETURN 0;
END;
/

CREATE OR REPLACE FUNCTION get_active_project_count
RETURN NUMBER IS
    v_count NUMBER;
BEGIN
    SELECT COUNT(*) INTO v_count FROM Project WHERE Status = 'Active';
    RETURN v_count;
EXCEPTION
    WHEN OTHERS THEN
        RETURN 0;
END;
/

CREATE OR REPLACE FUNCTION total_projects_by_supervisor(sid NUMBER)
RETURN NUMBER IS
    v_count NUMBER;
BEGIN
    SELECT COUNT(*) INTO v_count FROM Project WHERE S_Id = sid;
    RETURN v_count;
EXCEPTION
    WHEN NO_DATA_FOUND THEN
        RETURN 0;
    WHEN OTHERS THEN
        RAISE_APPLICATION_ERROR(-20002, 'Error in total_projects_by_supervisor: ' || SQLERRM);
END;
/


-- ============================================================
-- PART 6: STORED PROCEDURES (with EXCEPTION handling)
-- ============================================================

-- ── Department Procedures ─────────────────────

CREATE OR REPLACE PROCEDURE add_department(
    p_building IN VARCHAR2,
    p_name     IN VARCHAR2,
    p_phone    IN VARCHAR2
) IS
BEGIN
    IF p_name IS NULL OR TRIM(p_name) = '' THEN
        RAISE_APPLICATION_ERROR(-20010, 'Department name cannot be empty');
    END IF;
    IF p_building IS NULL OR TRIM(p_building) = '' THEN
        RAISE_APPLICATION_ERROR(-20011, 'Building cannot be empty');
    END IF;
    INSERT INTO Department VALUES (seq_department.NEXTVAL, p_building, p_name, p_phone);
    COMMIT;
EXCEPTION
    WHEN DUP_VAL_ON_INDEX THEN
        ROLLBACK;
        RAISE_APPLICATION_ERROR(-20012, 'A department with this name already exists');
    WHEN OTHERS THEN
        ROLLBACK;
        RAISE_APPLICATION_ERROR(-20099, 'add_department failed: ' || SQLERRM);
END;
/

CREATE OR REPLACE PROCEDURE update_department(
    p_id       IN NUMBER,
    p_building IN VARCHAR2,
    p_name     IN VARCHAR2,
    p_phone    IN VARCHAR2
) IS
    v_count NUMBER;
BEGIN
    SELECT COUNT(*) INTO v_count FROM Department WHERE D_Id = p_id;
    IF v_count = 0 THEN
        RAISE_APPLICATION_ERROR(-20013, 'Department ID ' || p_id || ' does not exist');
    END IF;
    UPDATE Department
       SET Building   = p_building,
           D_Name     = p_name,
           Office_Phn = p_phone
     WHERE D_Id = p_id;
    COMMIT;
EXCEPTION
    WHEN OTHERS THEN
        ROLLBACK;
        RAISE_APPLICATION_ERROR(-20099, 'update_department failed: ' || SQLERRM);
END;
/

CREATE OR REPLACE PROCEDURE delete_department(p_id IN NUMBER) IS
    v_count NUMBER;
BEGIN
    SELECT COUNT(*) INTO v_count FROM Researcher WHERE D_Id = p_id;
    IF v_count > 0 THEN
        RAISE_APPLICATION_ERROR(-20014,
            'Cannot delete: ' || v_count || ' researcher(s) belong to this department');
    END IF;
    DELETE FROM Department WHERE D_Id = p_id;
    COMMIT;
EXCEPTION
    WHEN OTHERS THEN
        ROLLBACK;
        RAISE;
END;
/

-- ── Fund Agency Procedures ────────────────────

CREATE OR REPLACE PROCEDURE add_fund_agency(
    p_name    IN VARCHAR2,
    p_country IN VARCHAR2,
    p_email   IN VARCHAR2,
    p_phone   IN VARCHAR2
) IS
BEGIN
    IF p_name IS NULL OR TRIM(p_name) = '' THEN
        RAISE_APPLICATION_ERROR(-20020, 'Agency name cannot be empty');
    END IF;
    IF p_phone IS NULL OR TRIM(p_phone) = '' THEN
        RAISE_APPLICATION_ERROR(-20021, 'Phone number is required for funding agency');
    END IF;
    INSERT INTO Fund_Agency VALUES (seq_fund.NEXTVAL, p_name, p_country, p_email, p_phone);
    COMMIT;
EXCEPTION
    WHEN DUP_VAL_ON_INDEX THEN
        ROLLBACK;
        RAISE_APPLICATION_ERROR(-20022, 'Agency email already exists in the system');
    WHEN OTHERS THEN
        ROLLBACK;
        RAISE_APPLICATION_ERROR(-20099, 'add_fund_agency failed: ' || SQLERRM);
END;
/

CREATE OR REPLACE PROCEDURE update_fund_agency(
    p_id      IN NUMBER,
    p_name    IN VARCHAR2,
    p_country IN VARCHAR2,
    p_email   IN VARCHAR2,
    p_phone   IN VARCHAR2
) IS
BEGIN
    UPDATE Fund_Agency
       SET F_Name    = p_name,
           Country   = p_country,
           Con_Email = p_email,
           Phone     = p_phone
     WHERE F_Id = p_id;
    IF SQL%ROWCOUNT = 0 THEN
        RAISE_APPLICATION_ERROR(-20023, 'Fund Agency ID ' || p_id || ' not found');
    END IF;
    COMMIT;
EXCEPTION
    WHEN OTHERS THEN
        ROLLBACK;
        RAISE_APPLICATION_ERROR(-20099, 'update_fund_agency failed: ' || SQLERRM);
END;
/

CREATE OR REPLACE PROCEDURE delete_fund_agency(p_id IN NUMBER) IS
    v_count NUMBER;
BEGIN
    SELECT COUNT(*) INTO v_count FROM Project WHERE F_Id = p_id;
    IF v_count > 0 THEN
        RAISE_APPLICATION_ERROR(-20024,
            'Cannot delete: ' || v_count || ' project(s) are funded by this agency');
    END IF;
    DELETE FROM Fund_Agency WHERE F_Id = p_id;
    COMMIT;
EXCEPTION
    WHEN OTHERS THEN
        ROLLBACK;
        RAISE;
END;
/

-- ── Publication Procedures ────────────────────

CREATE OR REPLACE PROCEDURE add_publication(
    p_title     IN VARCHAR2,
    p_doi       IN VARCHAR2,
    p_publisher IN VARCHAR2,
    p_year      IN NUMBER,
    p_cid       IN NUMBER,
    p_tid       IN NUMBER
) IS
    v_check NUMBER;
BEGIN
    IF p_title IS NULL OR TRIM(p_title) = '' THEN
        RAISE_APPLICATION_ERROR(-20030, 'Publication title cannot be empty');
    END IF;
    IF p_doi IS NULL OR TRIM(p_doi) = '' THEN
        RAISE_APPLICATION_ERROR(-20031, 'DOI cannot be empty');
    END IF;
    IF p_year < 1900 OR p_year > 2100 THEN
        RAISE_APPLICATION_ERROR(-20032, 'Invalid publication year: ' || p_year);
    END IF;
    -- Check DOI uniqueness manually
    SELECT COUNT(*) INTO v_check FROM Publication WHERE DOI = p_doi;
    IF v_check > 0 THEN
        RAISE_APPLICATION_ERROR(-20033, 'DOI already exists: ' || p_doi);
    END IF;
    INSERT INTO Publication VALUES
        (seq_publication.NEXTVAL, p_title, p_doi, p_publisher, p_year, p_cid, p_tid);
    COMMIT;
EXCEPTION
    WHEN OTHERS THEN
        ROLLBACK;
        RAISE_APPLICATION_ERROR(-20099, 'add_publication failed: ' || SQLERRM);
END;
/

CREATE OR REPLACE PROCEDURE update_publication(
    p_id        IN NUMBER,
    p_title     IN VARCHAR2,
    p_publisher IN VARCHAR2,
    p_year      IN NUMBER,
    p_cid       IN NUMBER,
    p_tid       IN NUMBER
) IS
BEGIN
    IF p_year < 1900 OR p_year > 2100 THEN
        RAISE_APPLICATION_ERROR(-20034, 'Invalid year: ' || p_year);
    END IF;
    UPDATE Publication
       SET Title     = p_title,
           Publisher = p_publisher,
           Year      = p_year,
           C_Id      = p_cid,
           T_Id      = p_tid
     WHERE P_Id = p_id;
    IF SQL%ROWCOUNT = 0 THEN
        RAISE_APPLICATION_ERROR(-20035, 'Publication ID ' || p_id || ' not found');
    END IF;
    COMMIT;
EXCEPTION
    WHEN OTHERS THEN
        ROLLBACK;
        RAISE_APPLICATION_ERROR(-20099, 'update_publication failed: ' || SQLERRM);
END;
/

CREATE OR REPLACE PROCEDURE delete_publication(p_id IN NUMBER) IS
BEGIN
    DELETE FROM Authors     WHERE P_Id = p_id;
    DELETE FROM Publication WHERE P_Id = p_id;
    COMMIT;
EXCEPTION
    WHEN OTHERS THEN
        ROLLBACK;
        RAISE_APPLICATION_ERROR(-20099, 'delete_publication failed: ' || SQLERRM);
END;
/

-- ── Publication Type Procedures ───────────────

CREATE OR REPLACE PROCEDURE add_pub_type(p_type IN VARCHAR2) IS
BEGIN
    IF p_type IS NULL OR TRIM(p_type) = '' THEN
        RAISE_APPLICATION_ERROR(-20040, 'Type name cannot be empty');
    END IF;
    INSERT INTO Publication_Type VALUES (seq_type.NEXTVAL, p_type);
    COMMIT;
EXCEPTION
    WHEN DUP_VAL_ON_INDEX THEN
        ROLLBACK;
        RAISE_APPLICATION_ERROR(-20041, 'This publication type already exists');
    WHEN OTHERS THEN
        ROLLBACK;
        RAISE_APPLICATION_ERROR(-20099, 'add_pub_type failed: ' || SQLERRM);
END;
/

CREATE OR REPLACE PROCEDURE update_pub_type(p_id IN NUMBER, p_type IN VARCHAR2) IS
BEGIN
    UPDATE Publication_Type SET Type = p_type WHERE T_Id = p_id;
    IF SQL%ROWCOUNT = 0 THEN
        RAISE_APPLICATION_ERROR(-20042, 'Publication Type ID ' || p_id || ' not found');
    END IF;
    COMMIT;
EXCEPTION
    WHEN OTHERS THEN
        ROLLBACK;
        RAISE_APPLICATION_ERROR(-20099, 'update_pub_type failed: ' || SQLERRM);
END;
/

CREATE OR REPLACE PROCEDURE delete_pub_type(p_id IN NUMBER) IS
    v_count NUMBER;
BEGIN
    SELECT COUNT(*) INTO v_count FROM Publication WHERE T_Id = p_id;
    IF v_count > 0 THEN
        RAISE_APPLICATION_ERROR(-20043,
            'Cannot delete: ' || v_count || ' publication(s) use this type');
    END IF;
    DELETE FROM Publication_Type WHERE T_Id = p_id;
    COMMIT;
EXCEPTION
    WHEN OTHERS THEN
        ROLLBACK;
        RAISE;
END;
/

-- ── Category Procedures ───────────────────────

CREATE OR REPLACE PROCEDURE add_category(
    p_name IN VARCHAR2,
    p_desc IN VARCHAR2
) IS
BEGIN
    IF p_name IS NULL OR TRIM(p_name) = '' THEN
        RAISE_APPLICATION_ERROR(-20050, 'Category name cannot be empty');
    END IF;
    INSERT INTO Category VALUES (seq_category.NEXTVAL, p_desc, p_name);
    COMMIT;
EXCEPTION
    WHEN OTHERS THEN
        ROLLBACK;
        RAISE_APPLICATION_ERROR(-20099, 'add_category failed: ' || SQLERRM);
END;
/

CREATE OR REPLACE PROCEDURE update_category(
    p_id   IN NUMBER,
    p_name IN VARCHAR2,
    p_desc IN VARCHAR2
) IS
BEGIN
    UPDATE Category
       SET C_Name      = p_name,
           Description = p_desc
     WHERE C_Id = p_id;
    IF SQL%ROWCOUNT = 0 THEN
        RAISE_APPLICATION_ERROR(-20051, 'Category ID ' || p_id || ' not found');
    END IF;
    COMMIT;
EXCEPTION
    WHEN OTHERS THEN
        ROLLBACK;
        RAISE_APPLICATION_ERROR(-20099, 'update_category failed: ' || SQLERRM);
END;
/

CREATE OR REPLACE PROCEDURE delete_category(p_id IN NUMBER) IS
    v_count NUMBER;
BEGIN
    SELECT COUNT(*) INTO v_count FROM Publication WHERE C_Id = p_id;
    IF v_count > 0 THEN
        RAISE_APPLICATION_ERROR(-20052,
            'Cannot delete: ' || v_count || ' publication(s) use this category');
    END IF;
    DELETE FROM Category WHERE C_Id = p_id;
    COMMIT;
EXCEPTION
    WHEN OTHERS THEN
        ROLLBACK;
        RAISE;
END;
/

-- ── Admin Procedures ──────────────────────────

CREATE OR REPLACE PROCEDURE add_admin(
    p_name  IN VARCHAR2,
    p_email IN VARCHAR2,
    p_pass  IN VARCHAR2
) IS
BEGIN
    IF p_name IS NULL OR TRIM(p_name) = '' THEN
        RAISE_APPLICATION_ERROR(-20060, 'Admin name cannot be empty');
    END IF;
    IF p_email IS NULL OR TRIM(p_email) = '' THEN
        RAISE_APPLICATION_ERROR(-20061, 'Admin email cannot be empty');
    END IF;
    INSERT INTO Admin VALUES (seq_admin.NEXTVAL, p_name, p_email, p_pass);
    COMMIT;
EXCEPTION
    WHEN DUP_VAL_ON_INDEX THEN
        ROLLBACK;
        RAISE_APPLICATION_ERROR(-20062, 'Admin email already exists: ' || p_email);
    WHEN OTHERS THEN
        ROLLBACK;
        RAISE_APPLICATION_ERROR(-20099, 'add_admin failed: ' || SQLERRM);
END;
/

CREATE OR REPLACE PROCEDURE update_admin(
    p_id    IN NUMBER,
    p_name  IN VARCHAR2,
    p_email IN VARCHAR2
) IS
BEGIN
    UPDATE Admin
       SET Name  = p_name,
           Email = p_email
     WHERE A_Id = p_id;
    IF SQL%ROWCOUNT = 0 THEN
        RAISE_APPLICATION_ERROR(-20063, 'Admin ID ' || p_id || ' not found');
    END IF;
    COMMIT;
EXCEPTION
    WHEN DUP_VAL_ON_INDEX THEN
        ROLLBACK;
        RAISE_APPLICATION_ERROR(-20064, 'That email is already used by another admin');
    WHEN OTHERS THEN
        ROLLBACK;
        RAISE_APPLICATION_ERROR(-20099, 'update_admin failed: ' || SQLERRM);
END;
/

CREATE OR REPLACE PROCEDURE delete_admin(p_id IN NUMBER) IS
    v_count NUMBER;
BEGIN
    SELECT COUNT(*) INTO v_count FROM Researcher WHERE A_Id = p_id;
    IF v_count > 0 THEN
        RAISE_APPLICATION_ERROR(-20065,
            'Cannot delete: ' || v_count || ' researcher(s) are managed by this admin');
    END IF;
    DELETE FROM Admin WHERE A_Id = p_id;
    COMMIT;
EXCEPTION
    WHEN OTHERS THEN
        ROLLBACK;
        RAISE;
END;
/


-- ============================================================
-- PART 7: TRIGGERS (with EXCEPTION handling)
-- ============================================================

-- Trigger 1 (ROW LEVEL): Prevent null researcher name on INSERT
CREATE OR REPLACE TRIGGER trg_check_researcher
BEFORE INSERT ON Researcher
FOR EACH ROW
BEGIN
    IF :NEW.Name IS NULL OR TRIM(:NEW.Name) = '' THEN
        RAISE_APPLICATION_ERROR(-20100, 'Researcher name cannot be NULL or empty');
    END IF;
    IF :NEW.Designation IS NULL THEN
        RAISE_APPLICATION_ERROR(-20101, 'Researcher designation cannot be NULL');
    END IF;
EXCEPTION
    WHEN OTHERS THEN
        RAISE;
END;
/

-- Trigger 2 (ROW LEVEL): Ensure fund agency phone is not null on INSERT
CREATE OR REPLACE TRIGGER trg_check_fund
BEFORE INSERT ON Fund_Agency
FOR EACH ROW
BEGIN
    IF :NEW.Phone IS NULL OR TRIM(:NEW.Phone) = '' THEN
        RAISE_APPLICATION_ERROR(-20102, 'Fund agency phone number is required');
    END IF;
    IF :NEW.Con_Email IS NULL OR TRIM(:NEW.Con_Email) = '' THEN
        RAISE_APPLICATION_ERROR(-20103, 'Fund agency contact email is required');
    END IF;
EXCEPTION
    WHEN OTHERS THEN
        RAISE;
END;
/

-- Trigger 3 (ROW LEVEL): Validate project dates on INSERT or UPDATE
CREATE OR REPLACE TRIGGER trg_validate_project_date
BEFORE INSERT OR UPDATE ON Project
FOR EACH ROW
BEGIN
    IF :NEW.E_Date IS NOT NULL AND :NEW.E_Date < :NEW.S_Date THEN
        RAISE_APPLICATION_ERROR(-20104, 'End date cannot be before start date');
    END IF;
    IF :NEW.Status NOT IN ('Active','Pending','Planning','Completed') THEN
        RAISE_APPLICATION_ERROR(-20105,
            'Invalid status. Must be Active, Pending, Planning, or Completed');
    END IF;
EXCEPTION
    WHEN OTHERS THEN
        RAISE;
END;
/

-- Trigger 4 (STATEMENT LEVEL): Log message after researcher inserted
CREATE OR REPLACE TRIGGER trg_after_insert_res
AFTER INSERT ON Researcher
BEGIN
    DBMS_OUTPUT.PUT_LINE('A new researcher has been inserted into the system.');
EXCEPTION
    WHEN OTHERS THEN
        NULL; -- Non-critical: do not fail the transaction for logging
END;
/

-- Trigger 5 (STATEMENT LEVEL): Log message after project inserted
CREATE OR REPLACE TRIGGER trg_after_insert_proj
AFTER INSERT ON Project
BEGIN
    DBMS_OUTPUT.PUT_LINE('A new research project has been created.');
EXCEPTION
    WHEN OTHERS THEN
        NULL;
END;
/


-- ============================================================
-- PART 8: PACKAGE (Specification + Body)
-- research_utils — reusable research utility functions
-- ============================================================

CREATE OR REPLACE PACKAGE research_utils AS
    FUNCTION  count_researchers                  RETURN NUMBER;
    FUNCTION  count_active_projects              RETURN NUMBER;
    FUNCTION  count_pubs_by_type(p_tid NUMBER)   RETURN NUMBER;
    PROCEDURE show_researchers_with_dept;
END research_utils;
/

CREATE OR REPLACE PACKAGE BODY research_utils AS

    -- Function 1: Count all researchers (used in admins.php)
    FUNCTION count_researchers RETURN NUMBER IS
        v_count NUMBER;
    BEGIN
        SELECT COUNT(*) INTO v_count FROM Researcher;
        RETURN v_count;
    EXCEPTION
        WHEN OTHERS THEN
            RETURN 0;
    END count_researchers;

    -- Function 2: Count active projects
    FUNCTION count_active_projects RETURN NUMBER IS
        v_count NUMBER;
    BEGIN
        SELECT COUNT(*) INTO v_count FROM Project WHERE Status = 'Active';
        RETURN v_count;
    EXCEPTION
        WHEN OTHERS THEN
            RETURN 0;
    END count_active_projects;

    -- Function 3: Count publications for a given type
    FUNCTION count_pubs_by_type(p_tid NUMBER) RETURN NUMBER IS
        v_count NUMBER;
    BEGIN
        SELECT COUNT(*) INTO v_count FROM Publication WHERE T_Id = p_tid;
        RETURN v_count;
    EXCEPTION
        WHEN NO_DATA_FOUND THEN
            RETURN 0;
        WHEN OTHERS THEN
            RETURN -1;
    END count_pubs_by_type;

    -- Procedure: Display researchers with their departments (uses explicit cursor)
    PROCEDURE show_researchers_with_dept IS
        CURSOR c_res IS
            SELECT r.Name, r.Designation, d.D_Name
            FROM   Researcher r
            JOIN   Department d ON r.D_Id = d.D_Id
            ORDER BY r.R_Id;
        rec c_res%ROWTYPE;
    BEGIN
        OPEN c_res;
        LOOP
            FETCH c_res INTO rec;
            EXIT WHEN c_res%NOTFOUND;
            DBMS_OUTPUT.PUT_LINE(
                RPAD(rec.Name, 20) || ' | ' ||
                RPAD(rec.Designation, 22) || ' | ' ||
                rec.D_Name
            );
        END LOOP;
        CLOSE c_res;
    EXCEPTION
        WHEN OTHERS THEN
            IF c_res%ISOPEN THEN
                CLOSE c_res;
            END IF;
            RAISE_APPLICATION_ERROR(-20099,
                'show_researchers_with_dept failed: ' || SQLERRM);
    END show_researchers_with_dept;

END research_utils;
/


-- ============================================================
-- PART 9: IMPLICIT & EXPLICIT LOCKING EXAMPLES
-- (For documentation — these run as anonymous blocks)
-- ============================================================

-- Implicit Lock Example 1: SELECT FOR UPDATE locks row before update
-- Used in researchers.php and projects.php delete operations
-- DECLARE
--     v_name VARCHAR2(100);
-- BEGIN
--     SELECT Name INTO v_name FROM Researcher WHERE R_Id = 1 FOR UPDATE;
--     UPDATE Researcher SET Re_Area = 'Updated Area' WHERE R_Id = 1;
--     COMMIT;
-- END;
-- /

-- Explicit Lock Example 1: LOCK TABLE prevents concurrent inserts
-- Used in researchers.php add operation
-- BEGIN
--     LOCK TABLE Researcher IN EXCLUSIVE MODE NOWAIT;
--     INSERT INTO Researcher VALUES(seq_researcher.NEXTVAL,'Test','pass','AI','Lecturer',1,1,1);
--     COMMIT;
-- END;
-- /

-- Explicit Lock Example 2: LOCK TABLE in SHARE MODE for read consistency
-- BEGIN
--     LOCK TABLE Project IN SHARE MODE;
--     -- safe to read project data here with no concurrent changes
--     COMMIT;
-- END;
-- /


-- ============================================================
-- VERIFICATION — Run after setup to confirm everything worked
-- ============================================================

SELECT 'Tables Created'     AS check_item, COUNT(*) AS count FROM user_tables
WHERE table_name IN ('ADMIN','LOCATION','DEPARTMENT','RESEARCHER','CONTACT',
                     'SUPERVISOR','FUND_AGENCY','PROJECT','WORK',
                     'CATEGORY','PUBLICATION_TYPE','PUBLICATION','AUTHORS')
UNION ALL
SELECT 'Sequences Created', COUNT(*) FROM user_sequences
WHERE sequence_name LIKE 'SEQ_%'
UNION ALL
SELECT 'Procedures Created', COUNT(*) FROM user_procedures
WHERE object_type = 'PROCEDURE'
UNION ALL
SELECT 'Functions Created', COUNT(*) FROM user_procedures
WHERE object_type = 'FUNCTION'
UNION ALL
SELECT 'Triggers Created', COUNT(*) FROM user_triggers
UNION ALL
SELECT 'Packages Created', COUNT(*) FROM user_objects
WHERE object_type = 'PACKAGE'
UNION ALL
SELECT 'Admin Rows',        COUNT(*) FROM Admin
UNION ALL
SELECT 'Researcher Rows',   COUNT(*) FROM Researcher
UNION ALL
SELECT 'Project Rows',      COUNT(*) FROM Project
UNION ALL
SELECT 'Publication Rows',  COUNT(*) FROM Publication;