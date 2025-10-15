CREATE TABLE messages (
                          id SERIAL PRIMARY KEY,
                          user_message TEXT NOT NULL,
                          bot_reply TEXT,
                          created_at TIMESTAMP DEFAULT NOW()
);