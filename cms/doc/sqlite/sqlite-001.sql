PRAGMA foreign_keys=OFF;
BEGIN TRANSACTION;
CREATE TABLE "cms_component" (
"id" INTEGER PRIMARY KEY,
"name" varchar(45),
"cms_componenttype_id" INTEGER REFERENCES "cms_componenttype"("id"),
"config" TEXT,
"is_enabled" char(1)
);
INSERT INTO "cms_component" VALUES(1,'testcomp',2,'CRUD','Y');
INSERT INTO "cms_component" VALUES(2,'test',1,'','Y');
INSERT INTO "cms_component" VALUES(3,'ooo',1,NULL,'Y');
INSERT INTO "cms_component" VALUES(4,'ooo',1,NULL,'Y');
INSERT INTO "cms_component" VALUES(5,'ooo',1,NULL,'Y');
INSERT INTO "cms_component" VALUES(6,'ooo',1,NULL,'Y');
INSERT INTO "cms_component" VALUES(7,'ooo',1,NULL,'Y');
INSERT INTO "cms_component" VALUES(8,'ooo',1,NULL,'Y');
INSERT INTO "cms_component" VALUES(9,'ooo',1,NULL,'Y');
INSERT INTO "cms_component" VALUES(10,'ooo',1,NULL,'Y');
INSERT INTO "cms_component" VALUES(11,'test',1,'YToxOntzOjQ6InRleHQiO3M6MjI6IkhlbGxvIFdvcmxkISAgYW9ldQ0KDQoiO30=','Y');
CREATE TABLE "cms_componenttype" (
"id" INTEGER PRIMARY KEY,
"name" varchar(255),
"class" varchar(255)
);
INSERT INTO "cms_componenttype" VALUES(1,'Text','cms/Cms_Text');
INSERT INTO "cms_componenttype" VALUES(2,'CRUD','cms/Cms_CRUD');
ANALYZE sqlite_master;
CREATE TABLE "cms_page" (
"id" INTEGER PRIMARY KEY AUTOINCREMENT,
"name" varchar(255),
"api_layout" varchar(255),
"page_layout" varchar(255)
);
INSERT INTO "cms_page" VALUES(1,'test',NULL,NULL);
INSERT INTO "cms_page" VALUES(2,'cms/qq',NULL,NULL);
CREATE TABLE "cms_route" (
"id" INTEGER PRIMARY KEY,
"rule" TEXT,
"target" TEXT,
"params" TEXT,
"ord" INTEGER
);
INSERT INTO "cms_route" VALUES(1,'(cms\/.*)','cms','cms_page',1);
CREATE TABLE cms_tag (id int not null  primary key, name varchar(255), value text);
CREATE TABLE "cms_pagecomponent" (
"id" INTEGER PRIMARY KEY,
"cms_page_id" int,
"cms_component_id" int,
"template_spot" TEXt,
"ord" INTEGER
);
INSERT INTO "cms_pagecomponent" VALUES(1,2,11,'Content',NULL);
DELETE FROM sqlite_sequence;
INSERT INTO "sqlite_sequence" VALUES('cms_page',2);
CREATE INDEX cms_tag_n on cms_tag(name);
COMMIT;
