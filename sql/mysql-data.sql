INSERT INTO strings (s,type) VALUES ('Cooking', 'skill');
INSERT INTO strings (s,type) VALUES ('Computers-Typing', 'skill');
INSERT INTO strings (s,type) VALUES ('Computers-Repair', 'skill');
INSERT INTO strings (s,type) VALUES ('Driving', 'skill');
INSERT INTO strings (s,type) VALUES ('Electrician', 'skill');
INSERT INTO strings (s,type) VALUES ('Graphic designer', 'skill');
INSERT INTO strings (s,type) VALUES ('Mentoring', 'skill');
INSERT INTO strings (s,type) VALUES ('Telephones', 'skill');
INSERT INTO strings (s,type) VALUES ('Plumber', 'skill');

INSERT INTO strings (type, lang, s) VALUES ('relationship', 'en_US', 'Family');
INSERT INTO strings (type, lang, s) VALUES ('relationship', 'en_US', 'Friend');
INSERT INTO strings (type, lang, s) VALUES ('relationship', 'en_US', 'Coworker');
INSERT INTO strings (type, lang, s) VALUES ('relationship', 'en_US', 'Professional');

INSERT INTO strings (type, lang, s) VALUES ('work', 'en_US', 'Outbound calling');
INSERT INTO strings (type, lang, s) VALUES ('work', 'en_US', 'Answering telephones');
INSERT INTO strings (type, lang, s) VALUES ('work', 'en_US', 'Landscaping');

INSERT into users (username, password, access_admin, access_change_vol) VALUES ('admin', md5('admin'), 1,1);

# for demo purposes below
INSERT INTO volunteers (first, last) VALUES ('C.', 'Lewis');
INSERT INTO volunteers (first, last) VALUES ('Simon', 'Peter');
INSERT INTO work (volunteer_id, date, hours) VALUES (1, now(), 10);
INSERT INTO work (volunteer_id, date, hours) VALUES (1, '2002-05-11', '3.2');
INSERT INTO work (volunteer_id, date, hours) VALUES (1, '2002-05-12', '8');
INSERT INTO notes (volunteer_id, uid_assigned, dt, reminder_date, message, acknowledged) VALUES (1, 1, now(), now(), 'Don\'t forget to change admin\'s password.\n\n  For support see http://sos.sourceforge.net', 1);