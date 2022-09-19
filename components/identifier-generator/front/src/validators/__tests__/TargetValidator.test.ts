import {validateTarget} from '../TargetValidator';

describe('TargetValidator', () => {
  it('should not add violation for valid target', () => {
    expect(validateTarget('sku', 'target')).toHaveLength(0);
  });

  it('should add violation with empty code', () => {
    expect(validateTarget('  ', 'target')).toEqual([{path: 'target', message: 'Target should not be empty'}]);
  });
});
