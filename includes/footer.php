<?php
include_once __DIR__ . '/nav.php';
?>
    <footer class="bg-gray-800 text-white mt-16">
        <div class="max-w-7xl mx-auto px-6 py-10">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <h3 class="text-lg font-semibold mb-3">LankanLens</h3>
                    <p class="text-sm text-gray-300">
                        Sri Lanka’s camera rental marketplace. Discover gear, connect with shops, and rent with confidence.
                    </p>
                </div>

                <div>
                    <h4 class="text-sm font-semibold uppercase tracking-wider text-gray-200 mb-3">Company</h4>
                    <ul class="space-y-2 text-sm text-gray-300">
                        <li><a href="<?php echo BASE_URL; ?>public/about.php" class="hover:text-white">About</a></li>
                        <li><a href="<?php echo BASE_URL; ?>public/contact.php" class="hover:text-white">Contact</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="text-sm font-semibold uppercase tracking-wider text-gray-200 mb-3">Legal</h4>
                    <ul class="space-y-2 text-sm text-gray-300">
                        <li><a href="<?php echo BASE_URL; ?>public/terms.php" class="hover:text-white">Terms &amp; Conditions</a></li>
                        <li><a href="<?php echo BASE_URL; ?>public/privacy.php" class="hover:text-white">Privacy Policy</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="text-sm font-semibold uppercase tracking-wider text-gray-200 mb-3">Follow Us</h4>
                    <ul class="space-y-2 text-sm text-gray-300">
                        <li><a href="https://facebook.com" target="_blank" rel="noopener" class="hover:text-white">Facebook</a></li>
                        <li><a href="https://instagram.com" target="_blank" rel="noopener" class="hover:text-white">Instagram</a></li>
                        <li><a href="https://twitter.com" target="_blank" rel="noopener" class="hover:text-white">Twitter / X</a></li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-gray-700 mt-8 pt-6 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                <p class="text-sm text-gray-300">&copy; <?php echo date('Y'); ?> LankanLens. All rights reserved.</p>
                <p class="text-xs text-gray-400">All prices shown in LKR (Sri Lankan Rupees).</p>
            </div>
        </div>
    </footer>
</body>
</html>
