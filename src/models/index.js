const sequelize = require('../config/database');
const Admin = require('./Admin');
const Profile = require('./Profile');
const Jumbotron = require('./Jumbotron');
const Organisasi = require('./Organisasi');
const Jabatan = require('./Jabatan');
const Berita = require('./Berita');
const Layanan = require('./Layanan');
const Kontak = require('./Kontak');

// Define associations
Organisasi.hasMany(Jabatan, { foreignKey: 'organisasi_id', as: 'jabatans' });
Jabatan.belongsTo(Organisasi, { foreignKey: 'organisasi_id', as: 'organisasi' });

module.exports = {
  sequelize,
  Admin,
  Profile,
  Jumbotron,
  Organisasi,
  Jabatan,
  Berita,
  Layanan,
  Kontak,
};
