require('dotenv').config();
const app = require('./src/app');
const { sequelize, Admin } = require('./src/models');
const bcrypt = require('bcryptjs');

const PORT = process.env.PORT || 3000;

const initializeDatabase = async () => {
  try {
    await sequelize.authenticate();
    console.log('Database connection established successfully.');

    // Sync all models
    await sequelize.sync({ alter: true });
    console.log('Database synchronized.');

    // Create default admin if not exists
    const adminCount = await Admin.count();
    if (adminCount === 0) {
      const hashedPassword = await bcrypt.hash(
        process.env.ADMIN_PASSWORD || 'admin123',
        10
      );
      await Admin.create({
        username: process.env.ADMIN_USERNAME || 'admin',
        password: hashedPassword,
      });
      console.log(
        `Default admin created: username="${process.env.ADMIN_USERNAME || 'admin'}". Please change the default password immediately.`
      );
    }
  } catch (error) {
    console.error('Unable to initialize database:', error);
    process.exit(1);
  }
};

const startServer = async () => {
  await initializeDatabase();

  app.listen(PORT, () => {
    console.log(`BPKAD Donggala API running on http://localhost:${PORT}`);
    console.log(`Environment: ${process.env.NODE_ENV || 'development'}`);
  });
};

startServer();
