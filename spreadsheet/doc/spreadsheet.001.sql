create table spreadsheet (
    id int not null auto_increment primary key,
    name varchar(255),
    cols int,
    rows int
);
create table spreadsheet_cell (
    id int not null auto_increment primary key,
    name varchar(255),
    content varchar(255),
    spreadsheet_id int
);

create index sn on spreadsheet_cell(spreadsheet_id, name);
