import React, {useCallback, useEffect, useState} from 'react';
import {
  PageContent as PageContentWithoutMargin,
  PageHeader,
  PimView,
  UnsavedChanges,
  useRoute,
  useTranslate,
} from '@akeneo-pim-community/shared';
import {
  BooleanInput,
  Breadcrumb,
  Button,
  Field as FieldWithoutMargin,
  Helper,
  SectionTitle,
  TextAreaInput,
} from 'akeneo-design-system';
import {LocaleSelector} from './components/LocaleSelector';
import {ConfigServicePayloadFrontend} from './models/ConfigServicePayload';
import {useFetchConfig, useSaveConfig} from './hooks';
import styled from 'styled-components';

const PageContent = styled(PageContentWithoutMargin)`
  padding-bottom: 40px;
`;

const Section = styled.div`
  display: flex;
  flex-direction: column;
`;

const Field = styled(FieldWithoutMargin)`
  margin-top: 20px;
`;

const ConfigForm = () => {
  const translate = useTranslate();

  const systemHref = useRoute('pim_system_index');

  const configFetchResult = useFetchConfig();

  // configuration object under edition
  const [config, setConfig] = useState<ConfigServicePayloadFrontend | null>(null);
  const [isModified, setIsModified] = useState(false);

  const modifyConfig = (config: ConfigServicePayloadFrontend) => {
    setConfig(config);
    setIsModified(true);
  };

  const handleChange = useCallback(
    (fieldName: keyof ConfigServicePayloadFrontend) => {
      return (value: boolean | string) => {
        if (!config) return;
        modifyConfig({
          ...config,
          [fieldName]: {
            ...config[fieldName],
            value,
          },
        });
      };
    },
    [config]
  );

  const doSaveConfig = useSaveConfig();

  const handleSave = async () => {
    if (config === null) return;
    const feedbackConfig = await doSaveConfig(config);
    setConfig(feedbackConfig);
    setIsModified(false);
  };

  useEffect(() => {
    if (configFetchResult.type === 'fetched') {
      //console.log('configFetchResult', { configFetchResult })
      setConfig({...configFetchResult.payload});
    }
  }, [configFetchResult]);

  if (configFetchResult.type === 'error') {
    return (
      <Helper level="error">
        {translate('Unexpected error occurred. Please contact system administrator.')}: {configFetchResult.message}
      </Helper>
    );
  }

  if (!config) return null;

  return (
    <>
      <PageHeader>
        <PageHeader.Breadcrumb>
          <Breadcrumb>
            <Breadcrumb.Step href={`#${systemHref}`}>{translate('pim_menu.tab.system')}</Breadcrumb.Step>
            <Breadcrumb.Step>{translate('pim_menu.item.configuration')}</Breadcrumb.Step>
          </Breadcrumb>
        </PageHeader.Breadcrumb>
        <PageHeader.UserActions>
          <PimView
            viewName="pim-menu-user-navigation"
            className="AknTitleContainer-userMenuContainer AknTitleContainer-userMenu"
          />
        </PageHeader.UserActions>
        <PageHeader.Actions>
          <Button className="AknButton--apply" onClick={handleSave}>
            Save
          </Button>
        </PageHeader.Actions>
        {isModified && (
          <PageHeader.State>
            <UnsavedChanges />
          </PageHeader.State>
        )}
        <PageHeader.Title>{translate('pim_menu.item.configuration')}</PageHeader.Title>
      </PageHeader>
      <PageContent>
        <Section>
          <SectionTitle>
            <SectionTitle.Title>{translate('oro_config.form.config.group.loading_message.title')}</SectionTitle.Title>
          </SectionTitle>
          <Helper level="info">{translate('oro_config.form.config.group.loading_message.helper')}</Helper>
          <Field
            data-testid="loading_message__enabler"
            label={translate('oro_config.form.config.group.loading_message.fields.enabler.label')}
          >
            <BooleanInput
              readOnly={false}
              value={config.pim_ui___loading_message_enabled.value}
              yesLabel={translate('pim_common.yes')}
              noLabel={translate('pim_common.no')}
              onChange={handleChange('pim_ui___loading_message_enabled')}
            />
          </Field>
          <Field label={translate('oro_config.form.config.group.loading_message.fields.messages.label')}>
            <TextAreaInput
              readOnly={false}
              value={config.pim_ui___loading_messages.value}
              onChange={handleChange('pim_ui___loading_messages')}
              placeholder={translate('oro_config.form.config.group.loading_message.placeholder')}
            />
          </Field>
        </Section>
        <Section>
          <SectionTitle>
            <SectionTitle.Title>{translate('oro_config.form.config.group.localization.title')}</SectionTitle.Title>
          </SectionTitle>
          <Field label={translate('oro_config.form.config.group.localization.fields.system_locale.label')}>
            <LocaleSelector value={config.pim_ui___language.value} onChange={handleChange('pim_ui___language')} />
          </Field>
        </Section>
        <Section>
          <SectionTitle>
            <SectionTitle.Title>{translate('oro_config.form.config.group.notification.title')}</SectionTitle.Title>
          </SectionTitle>
          <Helper level="info">{translate('oro_config.form.config.group.notification.helper')}</Helper>
          <Field
            data-testid="notification_message__enabler"
            label={translate('oro_config.form.config.group.notification.fields.enabler.label')}
          >
            <BooleanInput
              readOnly={false}
              value={config.pim_analytics___version_update.value}
              yesLabel={translate('pim_common.yes')}
              noLabel={translate('pim_common.no')}
              onChange={handleChange('pim_analytics___version_update')}
            />
          </Field>
        </Section>
      </PageContent>
    </>
  );
};

export {ConfigForm};
