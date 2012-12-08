drop table if exists webim_users;
create table webim_users(id integer primary key,
    name varchar(64),
    password varchar(64),
    last_access_time integer);
insert into webim_users values(0, 'foo', '123456', 0);
insert into webim_users values(1, 'bar', '123456', 0);

drop table if exists webim_friends;
create table webim_friends(userid integer, friendid integer);
insert into webim_friends values(0, 1);
insert into webim_friends values(1, 0);

drop table if exists webim_messages_0;
create table webim_messages_0(id integer primary key auto_increment,
    fromid integer,
    toid integer,
    content varchar(1024),
    time char(64));

drop table if exists webim_messages_1;
create table webim_messages_1(id integer primary key auto_increment,
    fromid integer,
    toid integer,
    content varchar(1024),
    time char(64));
