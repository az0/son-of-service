INSERT into skills (name) VALUES ('Cooking');
INSERT into skills (name) VALUES ('Computers-Typing');
INSERT into skills (name) VALUES ('Computers-Repair');
INSERT into skills (name) VALUES ('Driving');
INSERT into skills (name) VALUES ('Electrician');
INSERT into skills (name) VALUES ('Graphic designer');
INSERT into skills (name) VALUES ('Mentoring');
INSERT into skills (name) VALUES ('Telephones');
INSERT into skills (name) VALUES ('Plumber');

INSERT into users (username, password, access_admin, access_change_vol) VALUES ('admin', md5('admin'), 1,1);
INSERT INTO volunteers (first, last) VALUES ('C.', 'Lewis');
INSERT INTO work (volunteer_id, date, hours) VALUES (1, now(), 10);
INSERT INTO work (volunteer_id, date, hours) VALUES (1, '2002-05-11', '3.2');
INSERT INTO work (volunteer_id, date, hours) VALUES (1, '2002-05-12', '8');
