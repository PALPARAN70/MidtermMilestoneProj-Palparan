-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 09, 2026 at 09:14 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `kusina_ng_barangay`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` smallint(5) UNSIGNED NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`) VALUES
(4, 'Almusal'),
(3, 'Gulay'),
(6, 'Kakanin'),
(5, 'Merienda'),
(7, 'Panghimagas'),
(8, 'Pulutan'),
(2, 'Sabaw'),
(9, 'Tipid Meals'),
(1, 'Ulam');

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `id` int(10) UNSIGNED NOT NULL,
  `recipe_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `body` varchar(1000) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `comments`
--

INSERT INTO `comments` (`id`, `recipe_id`, `user_id`, `body`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'lamia uy!!! 100/10', '2026-10-09 14:28:39', '2026-10-09 15:04:20'),
(14, 2, 2, 'masarap po', '2026-10-09 15:02:36', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `favorites`
--

CREATE TABLE `favorites` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `recipe_id` int(10) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `favorites`
--

INSERT INTO `favorites` (`user_id`, `recipe_id`, `created_at`) VALUES
(1, 1, '2026-10-09 14:31:36'),
(1, 2, '2026-10-09 14:43:43');

-- --------------------------------------------------------

--
-- Table structure for table `ingredients`
--

CREATE TABLE `ingredients` (
  `id` int(10) UNSIGNED NOT NULL,
  `recipe_id` int(10) UNSIGNED NOT NULL,
  `position` smallint(5) UNSIGNED NOT NULL,
  `quantity` varchar(40) DEFAULT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ingredients`
--

INSERT INTO `ingredients` (`id`, `recipe_id`, `position`, `quantity`, `name`) VALUES
(4, 2, 1, '4kg', 'pork loin cutlets'),
(5, 2, 2, '1/2', 'flour'),
(6, 2, 3, '2', 'large eggs'),
(7, 2, 4, '1', 'breadcrumbs'),
(8, 2, 5, '1kg', 'butter'),
(16, 1, 1, '1 kg', 'beef ribs'),
(17, 1, 2, '2 pcs', 'tomato'),
(18, 1, 3, '2 pcs 50g', 'Sinigang mix'),
(19, 5, 1, '2kg', 'pork belly'),
(20, 5, 2, '2 pcs', 'tomato cube'),
(21, 5, 3, '3.5 cups', 'pork stock'),
(22, 5, 4, '2 cups', 'vinegar'),
(23, 5, 5, '3', 'onion'),
(24, 5, 6, '2', 'garlic'),
(25, 6, 1, '4kg', 'pork belly'),
(26, 6, 2, '3L', 'pork stock'),
(27, 6, 3, '100ml', 'vinegar'),
(28, 6, 4, '3 pcs', 'luya');

-- --------------------------------------------------------

--
-- Table structure for table `recipes`
--

CREATE TABLE `recipes` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `category_id` smallint(5) UNSIGNED NOT NULL,
  `title` varchar(120) NOT NULL,
  `description` varchar(500) NOT NULL,
  `steps` text NOT NULL,
  `servings` tinyint(3) UNSIGNED NOT NULL DEFAULT 4,
  `cook_minutes` smallint(5) UNSIGNED NOT NULL DEFAULT 30,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL
) ;

--
-- Dumping data for table `recipes`
--

INSERT INTO `recipes` (`id`, `user_id`, `category_id`, `title`, `description`, `steps`, `servings`, `cook_minutes`, `created_at`, `updated_at`) VALUES
(1, 1, 2, 'Easy Beef Ribs Sinigang Recipe', 'This one-pot dish combines the rich and flavorful taste of beef, mixed with the fresh garden vegetables, cooked in the tamarind based soup.', 'With this one pot easy dish, you will have less preparation in the kitchen and more time spent in the family table for conversations. You just need to put together the beef ribs, tomatoes, onions and 1 liter of water into a heavy pot. Let this boil over high heat and without a cover. Remember to remove the scum that accumulates on the top of the surface, make that soup clear and prevent it from becoming cloudy and visually unpleasant.\nOnce it boils, cover the pot and bring down the heat. You can now relax as this would cook for 1 hour or more. If the meat is fork tender, you know that it is done. At this point, pour the Knorr Sinigang sa Sampalok Original 20g.\nThrow in the radish slices and let this cook for 5 minutes before adding the eggplant. Once the eggplant is cooked through, add the sili and kangkong leaves and stems.\nGet your plates and fork ready because this Beef Ribs Sinigang is ready to be thoroughly enjoyed by your family!', 6, 75, '2026-10-09 14:28:25', '2026-10-09 15:04:11'),
(2, 2, 4, 'Traditional German Jägerschnitzel', 'A classic German comfort dish featuring tender, pan-fried pork cutlets topped with a deeply savory, creamy wild mushroom gravy', '1. Prep the Meat: Place the pork cutlets between sheets of plastic wrap. Pound them with a meat mallet until they are about ¼-inch thick. Season both sides generously with salt and pepper.\n2. Dredge and Bread: Set up three shallow bowls. Put flour in the first, beaten eggs in the second, and breadcrumbs in the third. Coat each cutlet in flour (shake off excess), dip in the egg, and then press into the breadcrumbs until fully coated.\n3. Fry the Schnitzel: Heat oil in a large skillet over medium-high heat. Fry the cutlets for 3 to 4 minutes per side until the crust is golden brown and crispy. Remove and set aside on paper towels to drain.\n4. Cook the Mushrooms: In a separate pan, melt the butter over medium heat. Add the diced onion and sliced mushrooms. Sauté for about 5 to 7 minutes until the mushrooms are browned and the moisture has evaporated.\n5. Build the Gravy: Sprinkle 1 tablespoon of flour over the mushrooms and stir constantly for 1 minute. Slowly pour in the beef broth while whisking to prevent lumps. Bring to a simmer and let it thicken for 3 minutes.\n6. Finish with Cream: Turn the heat to low. Stir in the heavy cream and let the sauce simmer for another 2 minutes until rich and velvety. Season with salt and pepper to taste.\n7. Serve: Plate the crispy schnitzels and spoon the hot mushroom gravy generously over the top. Garnish with fresh parsley.', 4, 30, '2026-10-09 14:37:04', NULL),
(5, 1, 4, 'Luis Specialty Binagoongan', 'Binagoongan is a Filipino dish wherein protein is cooked with shrimp paste. The usual protein used for this dish is pork. This binagoongan recipe makes use of pork belly. I also added eggplant to make it more interesting.', 'Heat oil in a cooking pot.\nSaute onion, garlic, and tomato. Once the onion becomes soft, add the pork belly. Cook until the belly turns light brown.\nAdd shrimp paste (bagoong alamang). Stir. Cook for 5 minutes.\nPour vinegar. Let the mixture boil. Stir and cook for 2 minutes.\nPour the pork stock into the pot. Cover and let boil. Adjust the heat to medium and continue to cook for 40 minutes or until the pork gets tender. Note: add water or pork stock as needed.\nAdd eggplant. Stir. Cover the pot and cook for 3 minutes.\nSeason with sugar and ground black pepper.\nTransfer to a serving plate. Serve. Share and enjoy!', 4, 30, '2026-10-09 15:08:45', NULL),
(6, 2, 1, 'Binagoongan James Version', 'Binagoongan is a Filipino dish wherein protein is cooked with shrimp paste. The usual protein used for this dish is pork. This binagoongan recipe makes use of pork belly. I also added eggplant to make it more interesting.', 'Heat oil in a cooking pot.\nSaute onion, garlic, and tomato. Once the onion becomes soft, add the pork belly. Cook until the belly turns light brown.\nAdd shrimp paste (bagoong alamang). Stir. Cook for 5 minutes.\nPour vinegar. Let the mixture boil. Stir and cook for 2 minutes.\nPour the pork stock into the pot. Cover and let boil. Adjust the heat to medium and continue to cook for 40 minutes or until the pork gets tender. Note: add water or pork stock as needed.\nAdd eggplant. Stir. Cover the pot and cook for 3 minutes.\nSeason with sugar and ground black pepper.\nTransfer to a serving plate. Serve. Share and enjoy!', 4, 30, '2026-10-09 15:10:26', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(60) NOT NULL,
  `email` varchar(120) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `created_at`) VALUES
(1, 'Luis Palparan', 'luis@gmail.com', '$2y$10$u23XfOeX2uu7oN26S5ACEu8MZu2q08Pb.CR6jr4g2veLBXLSe6m36', '2026-10-09 14:25:17'),
(2, 'james', 'henri@gmail.com', '$2y$10$5ed1M.PD48cff2h7bGjwJ.ItFPCHvwwq1ESpS6FdQFo7jI00Wkepa', '2026-10-09 14:31:54');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_categories_name` (`name`);

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_comments_recipe` (`recipe_id`,`created_at`),
  ADD KEY `fk_comments_user` (`user_id`);

--
-- Indexes for table `favorites`
--
ALTER TABLE `favorites`
  ADD PRIMARY KEY (`user_id`,`recipe_id`),
  ADD KEY `idx_favorites_recipe` (`recipe_id`);

--
-- Indexes for table `ingredients`
--
ALTER TABLE `ingredients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_ingredients_order` (`recipe_id`,`position`);

--
-- Indexes for table `recipes`
--
ALTER TABLE `recipes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_recipes_created` (`created_at`),
  ADD KEY `idx_recipes_category` (`category_id`),
  ADD KEY `fk_recipes_user` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_users_email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `ingredients`
--
ALTER TABLE `ingredients`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `recipes`
--
ALTER TABLE `recipes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `fk_comments_recipe` FOREIGN KEY (`recipe_id`) REFERENCES `recipes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_comments_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `favorites`
--
ALTER TABLE `favorites`
  ADD CONSTRAINT `fk_favorites_recipe` FOREIGN KEY (`recipe_id`) REFERENCES `recipes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_favorites_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ingredients`
--
ALTER TABLE `ingredients`
  ADD CONSTRAINT `fk_ingredients_recipe` FOREIGN KEY (`recipe_id`) REFERENCES `recipes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `recipes`
--
ALTER TABLE `recipes`
  ADD CONSTRAINT `fk_recipes_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_recipes_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
