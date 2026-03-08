import { execSync } from 'child_process';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const appRoot = path.resolve(__dirname, '..');

export default async function globalSetup() {
  console.log('\n[E2E] Running php artisan migrate:fresh --seed --seeder=E2eSeeder ...');
  execSync('php artisan migrate:fresh --seed --seeder=E2eSeeder', {
    cwd: appRoot,
    stdio: 'inherit',
  });
  console.log('[E2E] Database seeded successfully.\n');
}
