create table cms_tag (id int not null auto_increment primary key, name varchar(255), value text);
create index cms_tag_n on cms_tag(name);
