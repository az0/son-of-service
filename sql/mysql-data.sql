INSERT INTO strings (s, lang, type) VALUES ('Cooking', 'en_US', 'skill');
INSERT INTO strings (s, lang, type) VALUES ('Computers-Typing', 'en_US', 'skill');
INSERT INTO strings (s, lang, type) VALUES ('Computers-Repair', 'en_US', 'skill');
INSERT INTO strings (s, lang, type) VALUES ('Driving', 'en_US', 'skill');
INSERT INTO strings (s, lang, type) VALUES ('Electrician', 'en_US', 'skill');
INSERT INTO strings (s, lang, type) VALUES ('Graphic designer', 'en_US', 'skill');
INSERT INTO strings (s, lang, type) VALUES ('Mentoring', 'en_US', 'skill');
INSERT INTO strings (s, lang, type) VALUES ('Telephones', 'en_US', 'skill');
INSERT INTO strings (s, lang, type) VALUES ('Plumber', 'en_US', 'skill');

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
INSERT INTO work (volunteer_id, date, hours, category_id) VALUES (1, now(), 10, 14);
INSERT INTO work (volunteer_id, date, hours, category_id) VALUES (1, '2011-05-11', 3.2, 15);
INSERT INTO work (volunteer_id, date, hours, category_id) VALUES (1, '2011-05-12', 8, 16);
INSERT INTO notes (volunteer_id, uid_assigned, dt, reminder_date, message, acknowledged) VALUES (1, 1, now(), now(), 'Don\'t forget to change admin\'s password.\n\n  For support see http://sos.sourceforge.net', 0);
