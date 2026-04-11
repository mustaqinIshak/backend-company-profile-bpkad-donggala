const { DataTypes } = require('sequelize');
const sequelize = require('../config/database');

const Kontak = sequelize.define('Kontak', {
  id: {
    type: DataTypes.INTEGER,
    primaryKey: true,
    autoIncrement: true,
  },
  nama: {
    type: DataTypes.STRING(255),
    allowNull: false,
  },
  email: {
    type: DataTypes.STRING(100),
    allowNull: false,
  },
  telepon: {
    type: DataTypes.STRING(50),
    allowNull: true,
  },
  subjek: {
    type: DataTypes.STRING(255),
    allowNull: false,
  },
  pesan: {
    type: DataTypes.TEXT,
    allowNull: false,
  },
  status: {
    type: DataTypes.ENUM('belum_dibaca', 'sudah_dibaca', 'dibalas'),
    defaultValue: 'belum_dibaca',
  },
}, {
  tableName: 'kontaks',
  timestamps: true,
});

module.exports = Kontak;
