create table transaction (id int not null auto_increment primary key,
    name varchar(255),
    user_id int,
    amount decimal(12,4),
    is_completed enum('Y','N') default 'N',
    is_verified enum('Y','N') default 'N',
    deleted enum('Y','N') default 'N',
    created_dts datetime,
    modified_dts datetime,
    deleted_dts datetime,
    method varchar(255),
    aux varchar(255)
);

create index u on transaction(user_id);
create index c on transaction(is_confirmed);
create index v on transaction(is_verified);
create index m on transaction(method);
create index d on transaction(deleted);
