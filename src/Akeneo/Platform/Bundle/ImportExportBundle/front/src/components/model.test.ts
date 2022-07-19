import {FeatureFlags} from '@akeneo-pim-community/shared';
import {
  isValidStorageType,
  getDefaultStorage,
  isExport,
  getDefaultFilePath,
  localStorageIsEnabled,
  remoteStorageIsEnabled,
} from './model';

const featureFlagCollection = {
  job_automation_local_storage: false,
};

const enableFeatureFlag = (featureFlag: string) => (featureFlagCollection[featureFlag] = true);

const featureFlags: FeatureFlags = {
  isEnabled: (featureFlag: string) => featureFlagCollection[featureFlag],
};

beforeEach(() => {
  featureFlagCollection.job_automation_local_storage = false;
});

test('it says if a storage type is valid', () => {
  expect(isValidStorageType('local', featureFlags, 'xlsx_product_export')).toBe(false);

  enableFeatureFlag('job_automation_local_storage');

  expect(isValidStorageType('none', featureFlags, 'xlsx_product_export')).toBe(true);
  expect(isValidStorageType('local', featureFlags, 'xlsx_product_export')).toBe(true);
  expect(isValidStorageType('sftp', featureFlags, 'xlsx_product_export')).toBe(true);
  expect(isValidStorageType('sftp', featureFlags, 'xlsx_attribute_export')).toBe(false);
  expect(isValidStorageType('invalid', featureFlags, 'xlsx_product_export')).toBe(false);
});

test('it returns the default local storage', () => {
  expect(getDefaultStorage('export', 'local', 'xlsx')).toEqual({
    type: 'local',
    file_path: 'export_%job_label%_%datetime%.xlsx',
  });

  expect(getDefaultStorage('import', 'sftp', 'csv')).toEqual({
    type: 'sftp',
    file_path: 'myfile.csv',
    host: '',
    port: 22,
    username: '',
    password: '',
  });

  expect(getDefaultStorage('export', 'none', 'xlsx')).toEqual({
    type: 'none',
    file_path: 'export_%job_label%_%datetime%.xlsx',
  });

  // @ts-expect-error invalid storage type
  expect(() => getDefaultStorage('export', 'invalid', 'xlsx')).toThrowError('Unknown storage type: invalid');
});

test('it says if a job is an export', () => {
  expect(isExport('export')).toBe(true);
  expect(isExport('import')).toBe(false);
});

test('it returns the default file path', () => {
  expect(getDefaultFilePath('export', 'xlsx')).toBe('export_%job_label%_%datetime%.xlsx');
  expect(getDefaultFilePath('export', 'csv')).toBe('export_%job_label%_%datetime%.csv');
  expect(getDefaultFilePath('import', 'xlsx')).toBe('myfile.xlsx');
  expect(getDefaultFilePath('import', 'csv')).toBe('myfile.csv');
});

test('it check if local storage is enabled', () => {
  expect(localStorageIsEnabled(featureFlags)).toBe(false);
  enableFeatureFlag('job_automation_local_storage');
  expect(localStorageIsEnabled(featureFlags)).toBe(true);

  expect(remoteStorageIsEnabled('xlsx_product_import')).toBe(true);
  expect(remoteStorageIsEnabled('csv_attribute_import')).toBe(false);
});
