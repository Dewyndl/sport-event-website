ALTER TABLE users
    ADD COLUMN game_level TINYINT NOT NULL DEFAULT 0 COMMENT '0=Unset, 1=Beginner, 2=Beginner-Mid, 3=Middle, 4=Strong, 5=Super-Strong' AFTER phone;
