drop table if exists forum_category;
create table forum_category (id int not null auto_increment primary key,
    name varchar(255)
);
    
drop table if exists forum_thread;
create table forum_thread (id int not null auto_increment primary key,
    name varchar(255),
    forum_category_id int,
    user_id int,
    created_dts datetime,
    updated_dts datetime

);
drop table if exists forum_discussion;
create table forum_discussion (id int not null auto_increment primary key,
    name varchar(255),
    forum_thread_id int,
    user_id int,
    created_dts datetime,
    updated_dts datetime
);
drop table if exists forum_post;
create table forum_post (id int not null auto_increment primary key,
    name varchar(255),
    forum_discussion_id int,
    user_id int,
    created_dts datetime,
    updated_dts datetime
);
