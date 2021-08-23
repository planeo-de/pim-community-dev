import React, {createContext, FC, useCallback, useEffect, useState} from 'react';
import {AttributeOption, SpellcheckEvaluation} from '../model';
import {
  useCreateAttributeOption,
  useDeleteAttributeOption,
  useManualSortAttributeOptions,
  useSaveAttributeOption
} from '../hooks';
import {useAttributeContext} from "./AttributeContext";
import {useRoute} from '@akeneo-pim-community/shared';
import baseFetcher from '../fetchers/baseFetcher';

type AttributeOptionsState = {
  attributeOptions: AttributeOption[] | null;
  saveAttributeOption: (updatedAttributeOption: AttributeOption) => void;
  createAttributeOption: (optionCode: string) => AttributeOption;
  deleteAttributeOption: (attributeOptionId: number) => void;
  reorderAttributeOptions: (sortedAttributeOptions: AttributeOption[]) => void;
  isSaving: boolean;
};

const AttributeOptionsContext = createContext<AttributeOptionsState>({
  attributeOptions: null,
  saveAttributeOption: () => {},
  createAttributeOption: () => {},
  deleteAttributeOption: () => {},
  reorderAttributeOptions: () => {},
  isSaving: false,
});

type Props = {
  attributeOptionsQualityFetcher?: undefined | (() => Promise<SpellcheckEvaluation>),
};

const AttributeOptionsContextProvider: FC<Props> = ({children, attributeOptionsQualityFetcher}) => {
  const attribute = useAttributeContext();
  const [attributeOptions, setAttributeOptions] = useState<AttributeOption[] | null>(null);
  const attributeOptionSaver = useSaveAttributeOption();
  const attributeOptionCreate = useCreateAttributeOption();
  const attributeOptionDelete = useDeleteAttributeOption();
  const attributeOptionManualSort = useManualSortAttributeOptions();
  const [isSaving, setIsSaving] = useState<boolean>(false);
  const route = useRoute('pim_enrich_attributeoption_index', {attributeId: attribute.attributeId.toString()});

  useEffect(() => {
    (async () => {
      if (attributeOptions === null) {
        let attributeOptions = await baseFetcher(route);

        if (attributeOptionsQualityFetcher) {
          const attributeOptionsEvaluation: SpellcheckEvaluation = await attributeOptionsQualityFetcher();

          Object.entries(attributeOptionsEvaluation.options).forEach(([optionCode, optionEvaluation]) => {
            const optionIndex = attributeOptions.findIndex((attributeOption: AttributeOption) => attributeOption.code === optionCode);
            const attributeOptionToUpdate: AttributeOption = attributeOptions[optionIndex];
            attributeOptionToUpdate.toImprove = optionEvaluation.toImprove > 0;
            attributeOptions[optionIndex] = attributeOptionToUpdate;
          });
        }

        setAttributeOptions(attributeOptions);
      }
    })();
  }, []);

  const saveAttributeOption = useCallback(async(updatedAttributeOption: AttributeOption) => {
    if (!attributeOptions) {
      return;
    }
    setIsSaving(true);
    await attributeOptionSaver(updatedAttributeOption);
    const index = attributeOptions.findIndex(
      (attributeOption: AttributeOption) => attributeOption.id === updatedAttributeOption.id
    );

    let newAttributeOptions = [...attributeOptions];
    newAttributeOptions[index] = updatedAttributeOption;
    setAttributeOptions(newAttributeOptions);
    setIsSaving(false);
  }, [attributeOptions, attributeOptionSaver]);

  const createAttributeOption = useCallback(async(optionCode: string) => {
    setIsSaving(true);
    const attributeOption = await attributeOptionCreate(optionCode);
    if (attributeOptions === null) {
      setAttributeOptions([attributeOption]);
    } else {
      setAttributeOptions([...attributeOptions, attributeOption]);
    }
    setIsSaving(false);

    return attributeOption;
  }, [attributeOptions, attributeOptionCreate]);

  const deleteAttributeOption = useCallback(async(attributeOptionId: number) => {
    if (!attributeOptions) {
      return;
    }
    setIsSaving(true);
    await attributeOptionDelete(attributeOptionId);
    const index = attributeOptions.findIndex(
      (attributeOption: AttributeOption) => attributeOption.id === attributeOptionId
    );
    let newAttributeOptions = [...attributeOptions];
    newAttributeOptions.splice(index, 1);
    setAttributeOptions(newAttributeOptions);
    setIsSaving(false);
  }, [attributeOptions, attributeOptionDelete]);

  const reorderAttributeOptions = useCallback(async(sortedAttributeOptions: AttributeOption[]) => {
    setIsSaving(true);
    await attributeOptionManualSort(sortedAttributeOptions);
    setIsSaving(false);
  }, []);

  return <AttributeOptionsContext.Provider value={{attributeOptions, saveAttributeOption, createAttributeOption, deleteAttributeOption, reorderAttributeOptions, isSaving}}>
    {children}
  </AttributeOptionsContext.Provider>;
};

export {AttributeOptionsContextProvider, AttributeOptionsContext};
