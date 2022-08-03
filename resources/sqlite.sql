-- #! sqlite
-- #{ kygektagsshop

-- # { init
CREATE TABLE IF NOT EXISTS kygektagsshop
(
    player VARCHAR(32) PRIMARY KEY,
    tagid INT NOT NULL
);
-- # }

-- # { get
-- #    :player string
SELECT * FROM kygektagsshop
WHERE player = :player;
-- # }

-- # { insert
-- #    :player string
-- #    :tagid int
INSERT INTO kygektagsshop (player, tagid)
VALUES (:player, :tagid);
-- # }

-- # { update
-- #    :player string
-- #    :tagid int
UPDATE kygektagsshop
SET tagid = :tagid
WHERE player = :player;
-- # }

-- # { remove
-- #    :player string
DELETE FROM kygektagsshop WHERE player = :player;
-- # }

-- # { getall
SELECT * FROM kygektagsshop;
-- # }

-- # }