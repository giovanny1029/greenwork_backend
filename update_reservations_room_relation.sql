ALTER TABLE reservations
ADD CONSTRAINT fk_reservation_room
FOREIGN KEY (room_id)
REFERENCES rooms(id)
ON DELETE CASCADE;

CREATE INDEX idx_reservation_room
ON reservations(room_id);


