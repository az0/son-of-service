USE sos;

CREATE TABLE volunteers (
        volunteer_id int NOT NULL AUTO_INCREMENT PRIMARY KEY,
	
	entity_type CHAR NOT NULL DEFAULT 'I', # I=individual, G=group, O=organization
	
	organization varchar(60) NOT NULL,

	prefix varchar(20) NOT NULL,
        first varchar(20) NOT NULL,
        middle varchar(20) NOT NULL,
        last varchar(40) NOT NULL,
	suffix varchar(10) NOT NULL,

        street varchar(40) NOT NULL,
        city varchar(20) NOT NULL,
        state varchar(10) NOT NULL,
        zip varchar(10) NOT NULL,

        status varchar(20), # active, moved away, unreliable, unknown

        phone_home varchar(20) NOT NULL,
        phone_work varchar(20) NOT NULL,
        phone_cell varchar(20) NOT NULL,
        email_address varchar(45) NOT NULL,

	wants_monthly_information CHAR, # E (email), P (postal mail), N (no)
	
	hours_life_percenticle decimal(3,2),
	hours_ly_percenticle decimal(3,2),		
	hours_ytd_percenticle decimal(3,2),	
        hours_life decimal(10,2),
        hours_ly decimal(10,2),	
        hours_ytd decimal(10,2),
        first_volunteered date,

        dt_added datetime,
	uid_added int,
	dt_modified datetime,
	uid_modified int,	
	
	INDEX(first),
	INDEX(last),
	INDEX(organization),
	INDEX(street),
	INDEX(city),
	INDEX(zip),

        );
	
CREATE TABLE availability (
    availability_id  int NOT NULL AUTO_INCREMENT PRIMARY KEY,
    volunteer_id int NOT NULL,
    day_of_week tinyint, # 1 = Sunday, 7 = Saturday
    start_time enum("Morning", "Afternoon", "Evening", "Night"),
    end_time   enum("Morning", "Afternoon", "Evening", "Night"),

    dt_added datetime,
    uid_added int,
    dt_modified datetime,
    uid_modified int,	

    INDEX (volunteer_id),
    INDEX (day_of_week),
    INDEX (start_time),
    INDEX (end_time)
);


# describes a skill in general
CREATE TABLE skills (
	skill_id MEDIUMINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
	name varchar(100),
	UNIQUE(name)
);

# describe the each skill of each volunteer
CREATE TABLE volunteer_skills (
    volunteer_skill_id int NOT NULL AUTO_INCREMENT PRIMARY KEY,
    volunteer_id INT NOT NULL,
    skill_id INT NOT NULL,
    skill_level TINYINT NOT NULL,     # 1 = none, 2 = amatuer, 3 = some, 4 = pro, 5 = expert
#    PRIMARY KEY (volunteer_skill_id), 
    INDEX (volunteer_id), 
    INDEX (skill_id), 
    INDEX (skill_level),
    UNIQUE (volunteer_id, skill_id)
);

CREATE TABLE notes (
    note_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY UNIQUE,

    dt DATETIME,
    reminder_date DATE,
    uid_assigned INT, # user_id, if assigned to someone
    volunteer_id INT, # volunteer ID
    message TEXT NOT NULL,
    quality TINYINT, # -1 = bad, 0 = neutral, 1 = good
    uid_added INT, # user ID of he who added note
    uid_modified INT,
    dt_modified,

    INDEX (reminder_date),
    INDEX (volunteer_id)
);


CREATE TABLE work (
    work_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY UNIQUE,

    date DATE, # YYYY-MM-DD... for example, 2002-01-25
    hours DECIMAL(6,2), # for example, work of 6.25 hours
    volunteer_id INT, # volunteer ID
    memo TEXT,
    quality TINYINT, # -1 negative, 0 neutral, 1 positive

    dt_added DATETIME,
    uid_added INT,
    dt_modified DATETIME,
    uid_modified INT,	

    index (volunteer_id)
);


CREATE TABLE users (
        user_id int NOT NULL AUTO_INCREMENT PRIMARY KEY UNIQUE,

        personalname varchar(50),
        username varchar(20) UNIQUE,
	volunteer_id INT,
        password varchar(35),
        email varchar(50),	

        memo text,
        last_login datetime, # YYYY-MM-DD HH:MM:SS format

        access_admin bit, # change users, export/import files, etc.
        access_change_vol bit, # change volunteers

	INDEX (user_id),
	INDEX (username(5)),
	INDEX (password(5)));


CREATE TABLE log (
        log_id int NOT NULL AUTO_INCREMENT PRIMARY KEY,

        user_id int,
        level int,
        message tinytext,
        dt datetime # YYYY-MM-DD HH:MM:SS format
);

CREATE TABLE relationships (
	relationship_id NOT NULL AUTO_INCREOMENT PRIMARY KEY,
	
	volunteer1_id INT NOT NULL,
	volunteer2_id INT NOT NULL,	
	
	rtype int,

);

CREATE TABLE extended (
	extended_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
	volunteer_id NOT NULL,
	INDEX(volunteer_id)
);

CREATE TABLE extended_strings (
	extended_string_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
	extended_meta_id MEDIUMINT NOT NULL,
	lang CHAR(6),
	string varchar(100)
	
);

CREATE TABLE extended_meta (
	extended_meta_id MEDIUMINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
	
	code VARCHAR(50),
	label VARCHAR(50),
	description VARCHAR(100),
	databasecolumntype ENUM (int, float, decimal, datetime, date, time, varchar, blob),
	databasecolumnsize MEDIUMINT UNSIGNED,
	fieldtype (number, string, textarea, date, datetime, boolean, radio, select, multiselect, checkbox, file),
	displayposition MEDIUMINT,

# to do: validation
# to do: required, not required
	
	volunteer_read bool,
	volunteer_write bool,
	
	
	UNIQUE (code)
	
);


