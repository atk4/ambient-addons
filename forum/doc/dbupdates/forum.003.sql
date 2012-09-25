alter table forum_post drop forum_discussion_id;
alter table forum_post add (forum_post_id int, name varchar(255));

