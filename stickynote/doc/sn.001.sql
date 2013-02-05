create table stickynote (
    id int not null auto_increment primary key,
    name varchar(255),
    content text,
    is_global enum('Y','N') default 'N',
    url varchar(255),
    color varchar(255),
    user_id int,
    x int,
    y int,
    created_dts datetime
);
