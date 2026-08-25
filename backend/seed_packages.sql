INSERT IGNORE INTO users (id, name, email, password, role) VALUES 
(2, 'Oceanic Adventures', 'oceanic@safar.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'agency'),
(3, 'Mountain Treks', 'mountain@safar.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'agency'),
(4, 'City Escapes', 'city@safar.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'agency'),
(5, 'Desert Safari Co', 'desert@safar.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'agency'),
(6, 'Forest Explorers', 'forest@safar.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'agency'),
(7, 'Historical Tours', 'history@safar.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'agency');

INSERT IGNORE INTO agencies (id, user_id, company_name, phone, status) VALUES 
(1, 2, 'Oceanic Adventures', '1234567890', 'verified'),
(2, 3, 'Mountain Treks', '0987654321', 'verified'),
(3, 4, 'City Escapes', '1122334455', 'verified'),
(4, 5, 'Desert Safari Co', '9988776655', 'verified'),
(5, 6, 'Forest Explorers', '5544332211', 'verified'),
(6, 7, 'Historical Tours', '6677889900', 'verified');

INSERT IGNORE INTO packages (id, agency_id, title, location, price, description, image_url) VALUES 
(1, 1, 'Maldives Tropical Retreat', 'Maldives', 1499.00, 'Experience the ultimate relaxation with our 7-day tropical paradise package. Crystal clear waters and pristine beaches await you.', 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e'),
(2, 1, 'Bali Sunrise & Waves', 'Bali, Indonesia', 899.00, 'Discover the serene beaches and rich culture of Bali. Surf the best waves and relax in luxury resorts.', 'https://images.unsplash.com/photo-1507525428034-0a0f6c1e1e5c'),
(3, 1, 'Caribbean Island Hop', 'Bahamas', 2100.00, 'A wonderful 10-day island hopping experience across the beautiful Caribbean islands.', 'https://images.unsplash.com/photo-1493558103817-58b2924bce98'),

(4, 2, 'Himalayan Base Camp Trek', 'Nepal', 2100.00, 'Challenge yourself with a 14-day guided trek to the base of the worlds highest peak. An unforgettable journey.', 'https://images.unsplash.com/photo-1501785888041-af3ef285b470'),
(5, 2, 'Swiss Alps Adventure', 'Switzerland', 3200.00, 'A premium adventure package exploring the majestic Swiss Alps. Perfect for hiking enthusiasts and nature lovers.', 'https://images.unsplash.com/photo-1464822759023-fed622ff2c3b'),
(6, 2, 'Patagonia Wilderness', 'Argentina', 2800.00, 'Explore the dramatic landscapes, glaciers, and mountains of Patagonia on this 12-day expedition.', 'https://images.unsplash.com/photo-1472214103451-9374bd1c798e'),

(7, 3, 'Tokyo City Explorer', 'Tokyo, Japan', 1800.00, 'Immerse yourself in the bustling streets, rich history, and modern marvels of Tokyo.', 'https://images.unsplash.com/photo-1491553895911-0055eca6402d'),
(8, 3, 'New York Weekend Escapade', 'New York, USA', 1200.00, 'A fast-paced weekend exploring the city that never sleeps. Visit Times Square, Central Park, and more.', 'https://images.unsplash.com/photo-1467269204594-9661b134dd2b'),
(9, 3, 'Dubai Luxury Tour', 'Dubai, UAE', 2500.00, 'Experience luxury at its finest with our exclusive 5-day Dubai city tour.', 'https://images.unsplash.com/photo-1512453979798-5ea266f8880c'),

(10, 4, 'Sahara Desert Expedition', 'Morocco', 1350.00, 'A mesmerizing 5-day journey through the golden dunes of the Sahara. Includes camel rides and camping under the stars.', 'https://images.unsplash.com/photo-1509316785289-025f5b846b35'),
(11, 4, 'Atacama Stargazing', 'Chile', 1900.00, 'Discover the driest non-polar desert in the world and experience unparalleled stargazing.', 'https://images.unsplash.com/photo-1473580044384-7ba9967e16a0'),

(12, 5, 'Amazon Rainforest Safari', 'Brazil', 2200.00, 'Deep dive into the lungs of the Earth. A guided 8-day eco-tour through the Amazon rainforest.', 'https://images.unsplash.com/photo-1441974231531-c6227db76b6e'),
(13, 5, 'Yosemite Nature Walk', 'California, USA', 950.00, 'A peaceful 4-day retreat exploring the towering sequoias and beautiful valleys of Yosemite.', 'https://images.unsplash.com/photo-1501785888041-af3ef285b471'),

(14, 6, 'Rome Historical Immersion', 'Rome, Italy', 1600.00, 'Walk through history with our 6-day guided tour of Romes most famous ancient ruins and monuments.', 'https://images.unsplash.com/photo-1524492412937-b28074a5d7da'),
(15, 6, 'Machu Picchu Discovery', 'Peru', 2400.00, 'Uncover the mysteries of the Incas with this exclusive 7-day trek and tour of Machu Picchu.', 'https://images.unsplash.com/photo-1477587458883-47145ed94245');
