INSERT into skills (name) VALUES ('Cooking');
INSERT into skills (name) VALUES ('Computers-Typing');
INSERT into skills (name) VALUES ('Computers-Repair');
INSERT into skills (name) VALUES ('Driving');
INSERT into skills (name) VALUES ('Electrician');
INSERT into skills (name) VALUES ('Graphic designer');
INSERT into skills (name) VALUES ('Mentoring');
INSERT into skills (name) VALUES ('Telephones');
INSERT into skills (name) VALUES ('Plumber');

INSERT INTO relationship_types (name) VALUES ('Family');
INSERT INTO relationship_types (name) VALUES ('Friend');
INSERT INTO relationship_types (name) VALUES ('Coworker');
INSERT INTO relationship_types (name) VALUES ('Professional');

INSERT into users (username, password, access_admin, access_change_vol) VALUES ('admin', md5('admin'), 1,1);

# for demo purposes below
INSERT INTO volunteers (first, last) VALUES ('C.', 'Lewis');
INSERT INTO volunteers (first, last) VALUES ('Simon', 'Peter');
INSERT INTO relationships (volunteer1_id, volunteer2_id, rtype) VALUES (1, 2, 1);
INSERT INTO work (volunteer_id, date, hours) VALUES (1, now(), 10);
INSERT INTO work (volunteer_id, date, hours) VALUES (1, '2002-05-11', '3.2');
INSERT INTO work (volunteer_id, date, hours) VALUES (1, '2002-05-12', '8');
